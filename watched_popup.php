<?php
/**
 * watched_popup.php
 * ---------------------------------------------------------------
 * The "you just watched X" modal.
 *
 * Include near the bottom of details.php, before </body>:
 *
 *     <?php include("watched_popup.php"); ?>
 *
 * It renders nothing unless $justWatchedId is set, which details.php
 * does after a successful mark-as-watched redirect.
 * ---------------------------------------------------------------
 */

$justWatchedId     = $justWatchedId     ?? 0;
$justWatchedRating = $justWatchedRating ?? 0;

if ($justWatchedId <= 0) {
    return;
}
?>

<div class="jw-overlay" id="jwOverlay" role="dialog" aria-modal="true" aria-labelledby="jwTitle">
  <div class="jw-card">
    <button class="jw-close" id="jwClose" aria-label="Close">&times;</button>

    <div class="jw-header">
      <div class="jw-tick">&#10003;</div>
      <div>
        <h2 id="jwTitle">Added to your watched list</h2>
        <p class="jw-sub" id="jwSeedLine">Updating your recommendations&hellip;</p>
      </div>
    </div>

    <?php if ($justWatchedRating <= 0): ?>
      <div class="jw-rate-nudge">
        Rate it above and your recommendations get sharper &mdash; rated films
        carry more weight than unrated ones.
      </div>
    <?php endif; ?>

    <div id="jwBody">
      <p class="jw-loading">Finding films like this one&hellip;</p>
    </div>

    <div class="jw-footer">
      <a href="watched.php" class="jw-btn jw-btn-primary">Explore all recommendations</a>
      <button type="button" class="jw-btn jw-btn-ghost" id="jwDismiss">Keep browsing</button>
    </div>
  </div>
</div>

<style>
.jw-overlay {
  position: fixed;
  inset: 0;
  background: rgba(5, 8, 16, 0.82);
  backdrop-filter: blur(3px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.25s ease;
  padding: 20px;
}
.jw-overlay.active { opacity: 1; pointer-events: auto; }

.jw-card {
  background: #0f141c;
  border: 1px solid #242e40;
  border-radius: 14px;
  width: 100%;
  max-width: 720px;
  max-height: 88vh;
  overflow-y: auto;
  padding: 26px 28px 22px;
  position: relative;
  box-shadow: 0 24px 60px rgba(0,0,0,0.6);
  transform: translateY(14px);
  transition: transform 0.25s ease;
}
.jw-overlay.active .jw-card { transform: translateY(0); }

.jw-close {
  position: absolute;
  top: 14px; right: 16px;
  background: none; border: none;
  color: #64748b; font-size: 26px;
  cursor: pointer; line-height: 1;
}
.jw-close:hover { color: #e2e8f0; }

.jw-header { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 18px; }

.jw-tick {
  flex: 0 0 38px; height: 38px;
  border-radius: 50%;
  background: #14532d; color: #4ade80;
  display: flex; align-items: center; justify-content: center;
  font-size: 19px; font-weight: 700;
}
.jw-header h2 { margin: 0 0 4px; font-size: 1.18rem; color: #f1f5f9; }
.jw-sub { margin: 0; font-size: 0.86rem; color: #94a3b8; line-height: 1.4; }

.jw-rate-nudge {
  background: #1a2230; border: 1px solid #283448;
  border-radius: 8px; padding: 10px 12px;
  font-size: 0.8rem; color: #cbd5e1; margin-bottom: 18px;
}

.jw-section-title {
  font-size: 0.95rem; font-weight: 600; color: #e2e8f0;
  margin: 18px 0 10px;
}
.jw-section-title:first-child { margin-top: 0; }
.jw-section-note { font-size: 0.72rem; font-weight: 400; color: #64748b; margin-left: 8px; }

.jw-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(124px, 1fr));
  gap: 12px;
}
.jw-movie {
  background: #131924; border: 1px solid #242e40;
  border-radius: 8px; padding: 8px;
  text-decoration: none; display: block;
  transition: transform 0.18s ease, border-color 0.18s ease;
}
.jw-movie:hover { transform: translateY(-3px); border-color: #3b82f6; }
.jw-movie img {
  width: 100%; height: 160px; object-fit: cover;
  border-radius: 5px; display: block; background: #1a2230;
}
.jw-movie-title {
  color: #f1f5f9; font-size: 0.78rem; font-weight: 600;
  margin-top: 7px; line-height: 1.25;
}
.jw-movie-reason { color: #94a3b8; font-size: 0.68rem; margin-top: 3px; line-height: 1.25; }

.jw-loading, .jw-empty {
  color: #94a3b8; font-size: 0.86rem; text-align: center; padding: 22px 0;
}

.jw-footer {
  display: flex; gap: 10px; flex-wrap: wrap;
  margin-top: 22px; padding-top: 16px; border-top: 1px solid #1e2836;
}
.jw-btn {
  padding: 9px 16px; border-radius: 7px;
  font-size: 0.85rem; font-weight: 600;
  text-decoration: none; cursor: pointer;
  border: 1px solid transparent; transition: background 0.2s ease;
}
.jw-btn-primary { background: #2563eb; color: #fff; }
.jw-btn-primary:hover { background: #1d4ed8; }
.jw-btn-ghost { background: transparent; color: #94a3b8; border-color: #2b3648; }
.jw-btn-ghost:hover { color: #e2e8f0; border-color: #475569; }

@media (max-width: 520px) {
  .jw-card { padding: 20px 16px 16px; }
  .jw-movie img { height: 132px; }
}
</style>

<script>
(function () {
  const overlay  = document.getElementById("jwOverlay");
  const body     = document.getElementById("jwBody");
  const seedLine = document.getElementById("jwSeedLine");
  const seedId   = <?php echo (int) $justWatchedId; ?>;

  if (!overlay) return;

  const esc = str => String(str == null ? "" : str).replace(
    /[&<>"']/g,
    c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c])
  );

  function open()  { overlay.classList.add("active"); document.body.style.overflow = "hidden"; }
  function close() {
    overlay.classList.remove("active");
    document.body.style.overflow = "";
    // Drop the query flag so a refresh doesn't reopen the modal
    if (window.history.replaceState) {
      const url = new URL(window.location.href);
      url.searchParams.delete("just_watched");
      window.history.replaceState({}, "", url);
    }
  }

  document.getElementById("jwClose").addEventListener("click", close);
  document.getElementById("jwDismiss").addEventListener("click", close);
  overlay.addEventListener("click", e => { if (e.target === overlay) close(); });
  document.addEventListener("keydown", e => { if (e.key === "Escape") close(); });

  const card = m => `
    <a class="jw-movie" href="details.php?id=${encodeURIComponent(m.movie_id)}">
      <img src="${esc(m.poster)}" alt="${esc(m.title)}"
           onerror="this.onerror=null;this.src='default.jpg';">
      <div class="jw-movie-title">${esc(m.title)}${m.year ? " (" + esc(m.year) + ")" : ""}</div>
      <div class="jw-movie-reason">${esc(m.reason)}</div>
    </a>`;

  open();

  fetch("similar_to.php?movie_id=" + encodeURIComponent(seedId))
    .then(r => r.json())
    .then(data => {
      if (data.error) { body.innerHTML = '<p class="jw-empty">Could not load suggestions right now.</p>'; return; }

      if (data.seed && data.seed.title) {
        seedLine.textContent = "You watched " + data.seed.title
          + (data.seed.genres ? " \u2014 " + data.seed.genres : "");
      }

      let html = "";

      if (data.similar && data.similar.length) {
        html += `<div class="jw-section-title">Because you watched ${esc(data.seed.title)}
                   <span class="jw-section-note">more like this</span>
                 </div>
                 <div class="jw-grid">${data.similar.map(card).join("")}</div>`;
      }

      if (data.different && data.different.length) {
        html += `<div class="jw-section-title">Fancy something different?
                   <span class="jw-section-note">still your taste, different mood</span>
                 </div>
                 <div class="jw-grid">${data.different.map(card).join("")}</div>`;
      }

      body.innerHTML = html || '<p class="jw-empty">Mark a few more films as watched and suggestions will appear here.</p>';
    })
    .catch(err => {
      console.error("similar_to failed:", err);
      body.innerHTML = '<p class="jw-empty">Could not load suggestions right now.</p>';
    });
})();
</script>