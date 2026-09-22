<?php
/**
 * knn_lib.php
 * ---------------------------------------------------------------
 * Feature extraction and item-based KNN scoring for the movie
 * recommender. All the maths lives here so it can be tested
 * without a web request.
 *
 * Used by knn_recommendations.php.
 * ---------------------------------------------------------------
 */

// --- Tunables --------------------------------------------------
const KNN_K                = 5;     // neighbours per candidate -- the K in KNN
const KNN_GENRE_BOOST      = 2.5;   // genres matter more than keywords
const KNN_MOOD_BOOST       = 1.25;  // multiplier for mood-matching films
const KNN_MIN_SIM          = 0.02;  // below this, treat as no relationship
const KNN_MIN_TOKENS       = 10;    // films described by less than this are damped
const KNN_CACHE_DIR        = __DIR__ . '/rec_cache';

/**
 * Genre names that differ between your signup form, the mood map
 * and the TMDB data. Everything is canonicalised to the TMDB spelling.
 */
const KNN_SYNONYMS = [
    'sci-fi'    => 'science fiction',
    'scifi'     => 'science fiction',
    'sci fi'    => 'science fiction',
    'tv movie'  => 'tv movie',
    'animated'  => 'animation',
    'kids'      => 'family',
    'children'  => 'family',
    'musical'   => 'music',
    'rom-com'   => 'romance',
    'docu'      => 'documentary',
];

const KNN_STOPWORDS = ['the','a','an','of','and','or','in','on','at','to','for','with','by','from','is','it','as'];

/**
 * Normalise one genre or keyword name.
 *
 * IMPORTANT: this splits on commas only, never on whitespace, so
 * "Science Fiction" survives as a single concept. The old code split
 * on [,\s]+ which turned it into "science" + "fiction" and meant the
 * mood map could never match it.
 */
function knn_normalise(string $name): string
{
    $name = strtolower(trim($name));
    $name = preg_replace('/[^a-z0-9 \-]+/', ' ', $name);
    $name = trim(preg_replace('/\s+/', ' ', $name));

    if (isset(KNN_SYNONYMS[$name])) {
        $name = KNN_SYNONYMS[$name];
    }
    return $name;
}

/**
 * Split a genres or keywords column into normalised phrase names.
 *
 * Handles both storage formats in this project:
 *   - raw TMDB JSON:  [{"id": 28, "name": "Action"}, ...]
 *   - plain comma text from the admin form: "Action, Adventure"
 *
 * @return string[] e.g. ['action', 'science fiction']
 */
function knn_split_names(?string $raw): array
{
    if ($raw === null || trim($raw) === '') {
        return [];
    }
    $raw   = trim($raw);
    $names = [];

    if ($raw[0] === '[' || $raw[0] === '{') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                if (is_array($item) && isset($item['name'])) {
                    $names[] = (string) $item['name'];
                } elseif (is_string($item)) {
                    $names[] = $item;
                }
            }
        }
        if (!$names && preg_match_all('/["\']name["\']\s*:\s*["\'](.*?)["\']\s*[},]/', $raw, $m)) {
            $names = $m[1];
        }
    }

    if (!$names) {
        $names = preg_split('/\s*[,|;]\s*/', $raw) ?: [];
    }

    $out = [];
    foreach ($names as $n) {
        $n = knn_normalise($n);
        if ($n !== '' && strlen($n) >= 2) {
            $out[] = $n;
        }
    }
    return array_values(array_unique($out));
}

/**
 * Turn a phrase into index tokens: the phrase itself (spaces -> _)
 * plus its individual words, so "space travel" is findable both ways.
 *
 * @return string[]
 */
function knn_tokens_from_name(string $name): array
{
    $words  = explode(' ', $name);
    $tokens = [str_replace(' ', '_', $name)];

    if (count($words) > 1) {
        foreach ($words as $w) {
            if (strlen($w) >= 3 && !in_array($w, KNN_STOPWORDS, true)) {
                $tokens[] = $w;
            }
        }
    }
    return $tokens;
}

/**
 * Build the full feature model: vocabulary, IDF, and one sparse
 * L2-normalised TF-IDF vector per movie.
 *
 * Genres are counted KNN_GENRE_BOOST times so they outweigh keywords,
 * which is what you want for a genre-driven recommender, without
 * throwing away the keyword signal that breaks ties.
 *
 * Result is cached to disk and rebuilt automatically when the movie
 * table changes, so there is no cron job to remember.
 *
 * @return array{idf: array<string,float>, vectors: array<int,array<string,float>>,
 *               genres: array<int,string[]>, count: int}
 */
function knn_build_model(mysqli $conn, bool $useCache = true): array
{
    // Signature: any insert or delete changes this, invalidating the cache.
    $sig = 'v1';
    if ($res = $conn->query("SELECT COUNT(*) c, COALESCE(MAX(id),0) m FROM movies")) {
        $row = $res->fetch_assoc();
        $sig = 'v1_' . $row['c'] . '_' . $row['m'];
    }
    $cacheFile = KNN_CACHE_DIR . '/model_' . $sig . '.cache';

    if ($useCache && is_readable($cacheFile)) {
        $data = @unserialize((string) file_get_contents($cacheFile));
        if (is_array($data) && isset($data['vectors'])) {
            return $data;
        }
    }

    // ---- read the corpus -------------------------------------------------
    $docs   = [];   // id => token[] (with repeats = term frequency)
    $genres = [];   // id => genre name[]
    $df     = [];

    $res = $conn->query("SELECT id, genres, keywords FROM movies");
    while ($row = $res->fetch_assoc()) {
        $id = (int) $row['id'];

        $gNames = knn_split_names($row['genres']);
        $kNames = knn_split_names($row['keywords'] ?? '');

        $tokens = [];
        foreach ($gNames as $g) {
            foreach (knn_tokens_from_name($g) as $t) {
                // repeat the genre token so TF weights it higher
                for ($i = 0; $i < KNN_GENRE_BOOST; $i++) {
                    $tokens[] = $t;
                }
            }
        }
        foreach ($kNames as $k) {
            foreach (knn_tokens_from_name($k) as $t) {
                $tokens[] = $t;
            }
        }

        if (!$tokens) {
            continue;
        }

        $docs[$id]   = $tokens;
        $genres[$id] = $gNames;

        foreach (array_unique($tokens) as $t) {
            $df[$t] = ($df[$t] ?? 0) + 1;
        }
    }

    $N = count($docs);
    if ($N === 0) {
        return ['idf' => [], 'vectors' => [], 'genres' => [], 'count' => 0];
    }

    // Tokens in a single movie can never link two movies together.
    foreach ($df as $t => $c) {
        if ($c < 2) {
            unset($df[$t]);
        }
    }

    $idf = [];
    foreach ($df as $t => $c) {
        $idf[$t] = log(1.0 + ($N / $c));
    }

    $vectors = [];
    foreach ($docs as $id => $tokens) {
        $v = knn_vector($tokens, $idf);
        if ($v) {
            $vectors[$id] = $v;
        }
    }

    $nnz = [];
    foreach ($vectors as $id => $v) {
        $nnz[$id] = count($v);
    }

    $model = ['idf' => $idf, 'vectors' => $vectors, 'genres' => $genres,
              'nnz' => $nnz, 'count' => $N];

    if ($useCache) {
        if (!is_dir(KNN_CACHE_DIR)) {
            @mkdir(KNN_CACHE_DIR, 0775, true);
        }
        if (is_writable(KNN_CACHE_DIR)) {
            // clear stale models, then write
            foreach (glob(KNN_CACHE_DIR . '/model_*.cache') ?: [] as $old) {
                @unlink($old);
            }
            @file_put_contents($cacheFile, serialize($model), LOCK_EX);
        }
    }

    return $model;
}

/**
 * Sparse L2-normalised TF-IDF vector.
 * Sub-linear TF: (1 + log tf) * idf.
 *
 * @param string[]            $tokens
 * @param array<string,float> $idf
 * @return array<string,float>
 */
function knn_vector(array $tokens, array $idf): array
{
    if (!$tokens) {
        return [];
    }
    $tf  = array_count_values($tokens);
    $vec = [];

    foreach ($tf as $token => $count) {
        if (!isset($idf[$token])) {
            continue;
        }
        $w = (1.0 + log((float) $count)) * $idf[$token];
        if ($w > 0) {
            $vec[$token] = $w;
        }
    }

    $norm = 0.0;
    foreach ($vec as $w) {
        $norm += $w * $w;
    }
    if ($norm <= 0) {
        return [];
    }
    $norm = sqrt($norm);
    foreach ($vec as $t => $w) {
        $vec[$t] = $w / $norm;
    }
    return $vec;
}

/**
 * Confidence factor for a film with thin metadata.
 *
 * A movie tagged only "horror" scores a near-perfect cosine against any
 * horror-ish profile purely because its vector is one dimension long.
 * That is an artefact of L2 normalisation, not real relevance, so scale
 * such films down in proportion to how little we know about them.
 */
function knn_confidence(int $tokenCount): float
{
    if ($tokenCount >= KNN_MIN_TOKENS) {
        return 1.0;
    }
    return max(0.25, $tokenCount / KNN_MIN_TOKENS);
}

/** Cosine similarity of two normalised sparse vectors = dot product. */
function knn_cosine(array $a, array $b): float
{
    if (count($a) > count($b)) {
        [$a, $b] = [$b, $a];
    }
    $dot = 0.0;
    foreach ($a as $t => $w) {
        if (isset($b[$t])) {
            $dot += $w * $b[$t];
        }
    }
    return $dot;
}

/**
 * Map a 1-5 star rating to a profile weight.
 * Unrated but watched still counts positively; low stars push away.
 */
function knn_rating_weight($userRating): float
{
    if ($userRating === null || $userRating === '' || (float) $userRating <= 0) {
        return 0.6;                   // watched, not rated
    }
    $r = (float) $userRating;
    if ($r >= 5.0) return 1.0;
    if ($r >= 4.0) return 0.75;
    if ($r >= 3.0) return 0.35;
    if ($r >= 2.0) return -0.4;
    return -0.8;
}

/**
 * Build an inverted index token => movie_id[] so candidates can be
 * found without scanning every movie.
 *
 * @param array<int,array<string,float>> $vectors
 */
function knn_inverted_index(array $vectors): array
{
    $idx = [];
    foreach ($vectors as $id => $vec) {
        foreach ($vec as $token => $w) {
            $idx[$token][] = $id;
        }
    }
    return $idx;
}

/**
 * ---------------------------------------------------------------
 * THE RECOMMENDER
 *
 * True item-based KNN:
 *   for each candidate j
 *       find the K watched films most similar to j
 *       score(j) = SUM over those K of  w_i * cos(v_i, v_j)
 *
 * Only the K nearest watched films vote. A candidate weakly related
 * to thirty watched films no longer outranks one strongly related
 * to three -- which is exactly what a centroid cannot express.
 *
 * @param array $watched  [ ['id'=>int, 'rating'=>int|null], ... ]
 * @param array $opts     moodGenres, preferredGenres, limit
 * @return array ranked recommendations
 * ---------------------------------------------------------------
 */
function knn_recommend(array $model, array $watched, array $opts = []): array
{
    $vectors      = $model['vectors'];
    $genresById   = $model['genres'];
    $idf          = $model['idf'];
    $nnz          = $model['nnz'] ?? [];

    $moodGenres   = $opts['moodGenres']       ?? [];
    $prefGenres   = $opts['preferredGenres']  ?? [];
    $limit        = $opts['limit']            ?? 10;
    $k            = $opts['k']                ?? KNN_K;

    $watchedIds = [];
    $weights    = [];
    $ratings    = [];
    foreach ($watched as $w) {
        $id = (int) $w['id'];
        if (!isset($vectors[$id])) {
            continue;
        }
        $watchedIds[]   = $id;
        $weights[$id]   = knn_rating_weight($w['rating'] ?? null);
        $ratings[$id]   = ($w['rating'] ?? null) ? (float) $w['rating'] : 3.5;
    }

    // -----------------------------------------------------------
    // Candidate generation: only movies sharing at least one token
    // with something watched. Avoids scanning the whole table.
    // -----------------------------------------------------------
    $candidates = [];
    if ($watchedIds) {
        $inverted = knn_inverted_index($vectors);
        foreach ($watchedIds as $wid) {
            foreach ($vectors[$wid] as $token => $_) {
                if (!isset($inverted[$token])) {
                    continue;
                }
                foreach ($inverted[$token] as $cid) {
                    $candidates[$cid] = true;
                }
            }
        }
        foreach ($watchedIds as $wid) {
            unset($candidates[$wid]);          // never recommend what they watched
        }
    }

    // Cold start, or mood chosen with nothing watched: fall back to
    // scoring against a profile vector built from genres alone.
    $profileVec = [];
    $profileNames = array_merge($prefGenres, $moodGenres);
    if ($profileNames) {
        $ptokens = [];
        foreach ($profileNames as $n) {
            foreach (knn_tokens_from_name(knn_normalise($n)) as $t) {
                $ptokens[] = $t;
            }
        }
        $profileVec = knn_vector($ptokens, $idf);
    }

    if (!$candidates) {
        $candidates = array_fill_keys(array_keys($vectors), true);
        foreach ($watchedIds as $wid) {
            unset($candidates[$wid]);
        }
    }

    // -----------------------------------------------------------
    // Mood: a hard filter on the candidate set, not a +50 fudge on
    // the distance. Either a film fits the mood or it isn't offered.
    // -----------------------------------------------------------
    if ($moodGenres) {
        $moodSet = array_flip(array_map('knn_normalise', $moodGenres));
        foreach (array_keys($candidates) as $cid) {
            $hit = false;
            foreach (($genresById[$cid] ?? []) as $g) {
                if (isset($moodSet[$g])) {
                    $hit = true;
                    break;
                }
            }
            if (!$hit) {
                unset($candidates[$cid]);
            }
        }
    }

    // -----------------------------------------------------------
    // Score every candidate by its K nearest watched neighbours
    // -----------------------------------------------------------
    // With a mood active the profile matters more, so films at the heart
    // of that mood surface rather than only near-misses of past viewing.
    $profileWeight = $moodGenres ? 0.8 : 0.4;

    $out = [];
    foreach (array_keys($candidates) as $cid) {
        $cvec       = $vectors[$cid];
        $confidence = knn_confidence($nnz[$cid] ?? count($cvec));

        $sims = [];
        foreach ($watchedIds as $wid) {
            $s = knn_cosine($vectors[$wid], $cvec);
            if ($s >= KNN_MIN_SIM) {
                $sims[$wid] = $s;
            }
        }

        arsort($sims);
        $neighbours = array_slice($sims, 0, $k, true);

        $score          = 0.0;
        $simSum         = 0.0;
        $ratingWeighted = 0.0;
        $bestId         = null;
        $bestSim        = 0.0;

        foreach ($neighbours as $wid => $sim) {
            $score += $weights[$wid] * $sim;

            // KNN regression for the predicted rating, over the SAME
            // neighbours used for ranking -- so the badge and the
            // ordering now agree with each other.
            $ratingWeighted += $sim * $ratings[$wid];
            $simSum         += $sim;

            if ($sim > $bestSim && $weights[$wid] > 0) {
                $bestSim = $sim;
                $bestId  = $wid;
            }
        }

        // Profile contribution (preferred genres + mood affinity)
        $profileSim = $profileVec ? knn_cosine($profileVec, $cvec) : 0.0;
        $score += $profileWeight * $profileSim;

        // Damp films we barely have metadata for
        $score *= $confidence;

        if ($moodGenres) {
            $score *= KNN_MOOD_BOOST;
        }

        if ($score <= 0) {
            continue;                       // disliked-adjacent or unrelated
        }

        $predicted = $simSum > 0
            ? round(min(5.0, max(1.0, $ratingWeighted / $simSum)), 1)
            : null;

        $out[] = [
            'id'               => $cid,
            'score'            => round($score, 5),
            'predicted_rating' => $predicted,
            'neighbour_id'     => $bestId,
            'neighbour_sim'    => round($bestSim, 4),
            'profile_sim'      => round($profileSim, 4),
        ];
    }

    usort($out, function ($a, $b) {
        // Deterministic: score first, then id, so ties never reshuffle
        return ($b['score'] <=> $a['score']) ?: ($a['id'] <=> $b['id']);
    });

    return array_slice($out, 0, $limit);
}

/**
 * ---------------------------------------------------------------
 * Score an EXPLICIT set of candidate films against the user's taste.
 *
 * Unlike knn_recommend(), which only considers films sharing a token
 * with something watched, this scores exactly the ids you pass in --
 * used for guaranteed rows such as "Nepali films for you", where the
 * films must appear even if they don't overlap the (mostly English)
 * viewing history. Films with no usable metadata are still returned,
 * ranked last with a zero score, so the row is never empty.
 *
 * Scoring is otherwise identical to knn_recommend (K nearest watched
 * neighbours + preference/mood profile + confidence), but candidates
 * are NOT dropped when the score is <= 0.
 *
 * @param int[] $candidateIds
 * @return array ranked [ ['id','score','predicted_rating','neighbour_id'], ... ]
 * ---------------------------------------------------------------
 */
function knn_score_subset(array $model, array $watched, array $candidateIds, array $opts = []): array
{
    $vectors = $model['vectors'];
    $idf     = $model['idf'];
    $nnz     = $model['nnz'] ?? [];

    $moodGenres = $opts['moodGenres']      ?? [];
    $prefGenres = $opts['preferredGenres'] ?? [];
    $limit      = $opts['limit']           ?? 10;
    $k          = $opts['k']               ?? KNN_K;

    $watchedIds = [];
    $weights    = [];
    $ratings    = [];
    foreach ($watched as $w) {
        $id = (int) $w['id'];
        if (!isset($vectors[$id])) {
            continue;
        }
        $watchedIds[] = $id;
        $weights[$id] = knn_rating_weight($w['rating'] ?? null);
        $ratings[$id] = ($w['rating'] ?? null) ? (float) $w['rating'] : 3.5;
    }

    $profileVec = [];
    $profileNames = array_merge($prefGenres, $moodGenres);
    if ($profileNames) {
        $ptokens = [];
        foreach ($profileNames as $n) {
            foreach (knn_tokens_from_name(knn_normalise($n)) as $t) {
                $ptokens[] = $t;
            }
        }
        $profileVec = knn_vector($ptokens, $idf);
    }

    $profileWeight = $moodGenres ? 0.8 : 0.4;

    $out  = [];
    $seen = [];
    foreach ($candidateIds as $cid) {
        $cid = (int) $cid;
        if (isset($seen[$cid])) {
            continue;
        }
        $seen[$cid] = true;

        // No usable metadata: keep as a low-priority fallback so the row fills.
        if (!isset($vectors[$cid])) {
            $out[] = ['id' => $cid, 'score' => 0.0, 'predicted_rating' => null, 'neighbour_id' => null];
            continue;
        }

        $cvec       = $vectors[$cid];
        $confidence = knn_confidence($nnz[$cid] ?? count($cvec));

        $sims = [];
        foreach ($watchedIds as $wid) {
            $s = knn_cosine($vectors[$wid], $cvec);
            if ($s >= KNN_MIN_SIM) {
                $sims[$wid] = $s;
            }
        }
        arsort($sims);
        $neighbours = array_slice($sims, 0, $k, true);

        $score   = 0.0;
        $ratingW = 0.0;
        $simSum  = 0.0;
        $bestId  = null;
        $bestSim = 0.0;
        foreach ($neighbours as $wid => $sim) {
            $score   += $weights[$wid] * $sim;
            $ratingW += $sim * $ratings[$wid];
            $simSum  += $sim;
            if ($sim > $bestSim && $weights[$wid] > 0) {
                $bestSim = $sim;
                $bestId  = $wid;
            }
        }

        $profileSim = $profileVec ? knn_cosine($profileVec, $cvec) : 0.0;
        $score += $profileWeight * $profileSim;
        $score *= $confidence;
        if ($moodGenres) {
            $score *= KNN_MOOD_BOOST;
        }

        $predicted = $simSum > 0
            ? round(min(5.0, max(1.0, $ratingW / $simSum)), 1)
            : null;

        $out[] = [
            'id'               => $cid,
            'score'            => round($score, 5),
            'predicted_rating' => $predicted,
            'neighbour_id'     => $bestId,
        ];
    }

    usort($out, fn($a, $b) => ($b['score'] <=> $a['score']) ?: ($a['id'] <=> $b['id']));

    return array_slice($out, 0, $limit);
}