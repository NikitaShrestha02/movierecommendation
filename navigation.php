<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$navAuthed  = !empty($_SESSION['uemail']);
// The admin is a monitoring account: it must be able to open the public site
// (adnav.php's "View Site" opens index.php in a new tab), so it is exempt from
// the per-tab logout guard below. Regular users still get tab-close logout.
$navIsAdmin = ($_SESSION['uemail'] ?? '') === 'snadmin@gmail.com';
$navFresh   = !empty($_SESSION['fresh_login']);
if ($navFresh) { unset($_SESSION['fresh_login']); } // consume once
?>
<?php if ($navAuthed && !$navIsAdmin): ?>
<script>
/* ---------------------------------------------------------------
   Tab-close logout.
   A tab authorises itself only for its own lifetime. sessionStorage is
   per-tab and is wiped the instant the tab is closed, so a reopened tab
   has no key and is sent to logout. A tab that just logged in sets the
   key instead of logging out (the fresh-login handshake below).

   Trade-off: the PHP session is shared across the browser, so opening the
   site in a SECOND tab counts as an unauthorised tab and will log out too.
   For "logged out when the browser closes" only (multi-tab friendly),
   delete this whole <script> block -- the session cookies already handle
   that on their own.
   --------------------------------------------------------------- */
(function () {
  var KEY = "mr_tab_auth";
  var fresh = <?php echo $navFresh ? 'true' : 'false'; ?>;
  try {
    if (fresh) {
      sessionStorage.setItem(KEY, "1");        // this tab just logged in
    } else if (!sessionStorage.getItem(KEY)) {
      window.location.replace("logout.php");   // reopened / new tab -> log out
    }
  } catch (e) {
    /* sessionStorage blocked: fall back to browser-close logout only */
  }
})();
</script>
<?php endif; ?>
<style>
header.site-header {
  background-color: #131924;
  height: 64px;
  width: 100%;
  border-bottom: 1px solid #1f2838;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 0 24px;
  box-sizing: border-box;
  position: relative;
  z-index: 1000;
}

.site-header .nav-container {
  width: 100%;
  max-width: 1200px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.site-header ul.nav-menu {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 0;
  margin: 0;
  list-style: none;
}

.site-header ul.nav-menu > li {
  display: flex;
  align-items: center;
}

.site-header ul.nav-menu > li > a {
  color: #cbd5e1;
  text-decoration: none;
  font-size: 15px;
  font-weight: 500;
  padding: 6px 10px;
  border-radius: 5px;
  transition: color 0.2s ease, background-color 0.2s ease;
}

.site-header ul.nav-menu > li > a:hover {
  color: #ffffff;
  background-color: #1a2332;
  text-decoration: none;
}

.site-header .logo img {
  height: 38px;
  width: auto;
  display: block;
}

.site-header .user img {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid #334155;
  cursor: pointer;
  transition: border-color 0.2s ease, transform 0.15s ease;
  display: block;
}

.site-header .user img:hover {
  border-color: #3b82f6;
  transform: scale(1.04);
}

/* Search bar styles */
#search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background-color: #1a2230;
  border-radius: 6px;
  padding: 2px 4px 2px 10px;
  height: 38px;
  border: 1px solid #2d384c;
  min-width: 270px;
  box-sizing: border-box;
  transition: border-color 0.2s ease;
}

#search-wrapper:focus-within {
  border-color: #3b82f6;
}

#searchInput {
  padding: 6px 8px 6px 0;
  font-size: 14px;
  background-color: transparent;
  border: none;
  color: #f1f5f9;
  width: 100%;
  outline: none;
  font-family: inherit;
}

#searchInput::placeholder {
  color: #64748b;
}

#searchButton {
  padding: 6px 14px;
  border: none;
  background-color: #2563eb;
  color: #ffffff;
  font-weight: 600;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
  font-family: inherit;
  transition: background-color 0.2s ease;
  white-space: nowrap;
}

#searchButton:hover {
  background-color: #1d4ed8;
}

#results {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  width: 100%;
  background: #1a2230;
  border: 1px solid #2d384c;
  max-height: 250px;
  overflow-y: auto;
  z-index: 1001;
  border-radius: 6px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.4);
}

.result-item {
  padding: 10px 14px;
  border-bottom: 1px solid #242e40;
  cursor: pointer;
  color: #cbd5e1;
  font-size: 13.5px;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.result-item:last-child {
  border-bottom: none;
}

.result-item:hover {
  background-color: #242f42;
  color: #ffffff;
}

@media (max-width: 768px) {
  header.site-header {
    height: auto;
    padding: 12px 16px;
  }
  .site-header ul.nav-menu {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }
  #search-wrapper {
    min-width: 100%;
  }
}

/* Mood Popup Modal Overlay */
.mood-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(11, 15, 23, 0.85);
  backdrop-filter: blur(6px);
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease;
  padding: 20px;
  box-sizing: border-box;
}

.mood-modal-overlay.active {
  opacity: 1;
  visibility: visible;
}

.mood-modal-card {
  background: #181f2c;
  border: 1px solid #283448;
  border-radius: 12px;
  width: 100%;
  max-width: 780px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.6);
  padding: 28px 24px;
  position: relative;
  color: #cbd5e1;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.mood-modal-close {
  position: absolute;
  top: 18px;
  right: 20px;
  background: #1e2637;
  border: 1px solid #2d384c;
  color: #94a3b8;
  font-size: 20px;
  line-height: 1;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.mood-modal-close:hover {
  background: #dc2626;
  color: #ffffff;
  border-color: #dc2626;
}

.mood-modal-header h2 {
  margin: 0 0 6px 0;
  color: #f8fafc;
  font-size: 22px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
}

.mood-modal-header p {
  margin: 0;
  color: #94a3b8;
  font-size: 14px;
  line-height: 1.4;
}

.mood-buttons-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 10px;
}

.mood-pill-btn {
  background: #131924;
  border: 1px solid #263347;
  color: #cbd5e1;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  transition: all 0.2s ease;
  outline: none;
  font-family: inherit;
}

.mood-pill-btn:hover {
  border-color: #3b82f6;
  color: #ffffff;
  background: #1d2636;
  transform: translateY(-2px);
}

.mood-pill-btn.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
}

.mood-recommendations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 16px;
  padding-right: 4px;
}

/* Scrolling moved off the grid and onto the wrapper, so the two
   sections scroll together as one list instead of separately. */
#moodModalResults {
  max-height: 460px;
  overflow-y: auto;
  padding-right: 4px;
}

.rec-section-heading {
  font-size: 14px;
  font-weight: 600;
  color: #e2e8f0;
  margin: 20px 0 12px;
  display: flex;
  align-items: baseline;
  gap: 10px;
}

.rec-section-heading:first-child {
  margin-top: 0;
}

.rec-section-note {
  font-size: 11px;
  font-weight: 400;
  color: #64748b;
  letter-spacing: 0.02em;
}

.mood-movie-card {
  background: #131924;
  border: 1px solid #242e40;
  border-radius: 8px;
  padding: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  transition: transform 0.2s ease, border-color 0.2s ease;
}

.mood-movie-card:hover {
  transform: translateY(-3px);
  border-color: #3b82f6;
}

.mood-movie-card img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-radius: 6px;
  display: block;
}

.mood-movie-title {
  color: #f1f5f9;
  font-size: 13px;
  font-weight: 600;
  margin-top: 8px;
  text-align: center;
  line-height: 1.3;
}

.mood-match-badge {
  font-size: 11px;
  color: #f59e0b;
  font-weight: 600;
  background: #1a2230;
  border: 1px solid #283448;
  padding: 3px 6px;
  border-radius: 4px;
  margin-top: 6px;
  width: 100%;
  text-align: center;
  box-sizing: border-box;
}

.mood-explanation-text {
  font-size: 10.5px;
  color: #94a3b8;
  margin-top: 5px;
  text-align: center;
  line-height: 1.25;
}
</style>

<?php 
$autoTriggerMoodModal = false;
if (isset($_SESSION['show_mood_modal']) && $_SESSION['show_mood_modal'] === true) {
    $autoTriggerMoodModal = true;
    unset($_SESSION['show_mood_modal']);
}
?>
<header class="site-header">
  <div class="nav-container">
    <ul class="nav-menu">
      <li class="logo"><a href="index.php" style="padding:0;background:none;"><img src="LOGOO.png" alt="Logo"></a></li>
      <li><a href="index.php">Home</a></li>
      <li><a href="posters.php">Movies</a></li>
      <?php if (isset($_SESSION['uemail'])): ?>
        <li><a href="watched.php">For You</a></li>
      <?php endif; ?>

      <li class="search-bar">
        <div id="search-wrapper">
          <input type="text" id="searchInput" placeholder="Search by keyword..." autocomplete="off" />
          <button id="searchButton" type="button">Search</button>
          <div id="results"></div>
        </div>

        <script>
          function triggerMainSearch(keyword) {
            const mainContainer = document.getElementById('main-recommendations-container');
            if (mainContainer) {
              fetch('fetch_recommendations.php?search=' + encodeURIComponent(keyword))
                .then(res => res.text())
                .then(html => {
                  mainContainer.innerHTML = html;
                });
            } else {
              window.location.href = 'index.php?search=' + encodeURIComponent(keyword);
            }
          }

          document.getElementById('searchInput').addEventListener('keyup', function (e) {
            const keyword = this.value.trim();
            
            if (e.key === 'Enter') {
              if (keyword.length < 2) {
                alert("Please type at least 2 characters.");
                return;
              }
              document.getElementById('results').innerHTML = '';
              triggerMainSearch(keyword);
              return;
            }

            if (keyword.length < 2) {
              document.getElementById('results').innerHTML = '';
              return;
            }

            fetch('search_filter.php?search=' + encodeURIComponent(keyword))
              .then(response => response.text())
              .then(data => {
                document.getElementById('results').innerHTML = data;
              });
          });

          document.getElementById('searchButton').addEventListener('click', function () {
            const keyword = document.getElementById('searchInput').value.trim();
            if (keyword.length < 2) {
              alert("Please type at least 2 characters.");
              return;
            }
            document.getElementById('results').innerHTML = '';
            triggerMainSearch(keyword);
          });

          // Close search results dropdown on outside click
          document.addEventListener('click', function (e) {
            const wrapper = document.getElementById('search-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
              const res = document.getElementById('results');
              if (res) res.innerHTML = '';
            }
          });
        </script>
      </li>

      <li><a href="about.php">About</a></li>
      <li><a href="contact.php">Contact</a></li>
      <li class="user"><a href="userdash.php" style="padding:0;background:none;"><img src="userr.jpg" alt="User Profile"></a></li>
    </ul>
  </div>
</header>
<!-- Mood Recommendation Popup Modal -->
<div class="mood-modal-overlay" id="moodModalOverlay">
  <div class="mood-modal-card">
    <button class="mood-modal-close" id="closeMoodModalBtn">&times;</button>
    <div class="mood-modal-header">
      <h2>🎭 How are you feeling today?</h2>
      <p>Select your current mood to get instant personalized movie recommendations based on your preferences and viewing history!</p>
    </div>

    <!-- Mood Buttons Grid -->
    <div class="mood-buttons-grid" id="moodModalPillGrid">
      <button class="mood-pill-btn" data-mood="Happy">😊 Happy</button>
      <button class="mood-pill-btn" data-mood="Relaxed">😌 Relaxed</button>
      <button class="mood-pill-btn" data-mood="Sad">😢 Sad</button>
      <button class="mood-pill-btn" data-mood="Excited">🔥 Excited</button>
      <button class="mood-pill-btn" data-mood="Romantic">❤️ Romantic</button>
      <button class="mood-pill-btn" data-mood="Adventurous">🧗 Adventurous</button>
      <button class="mood-pill-btn" data-mood="Thoughtful">🤔 Thoughtful</button>
      <button class="mood-pill-btn" data-mood="Chill">🛋️ Chill</button>
    </div>

    <!-- Recommendations Output inside Modal -->
    <div id="moodModalResults">
      <p style="text-align: center; color: #94a3b8; padding: 36px 0; margin: 0; font-size: 14.5px;">
        Tap any mood above to discover movies matched to your current vibe!
      </p>
    </div>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const modalOverlay = document.getElementById("moodModalOverlay");
    const closeModalBtn = document.getElementById("closeMoodModalBtn");
    const openModalBtn = document.getElementById("openMoodModalBtn");
    const pillGrid = document.getElementById("moodModalPillGrid");
    const resultsContainer = document.getElementById("moodModalResults");

    const autoTrigger = <?php echo $autoTriggerMoodModal ? 'true' : 'false'; ?>;

    function openMoodModal() {
      if (modalOverlay) {
        modalOverlay.classList.add("active");
      }
    }

    function closeMoodModal() {
      if (modalOverlay) {
        modalOverlay.classList.remove("active");
      }
    }

    if (autoTrigger) {
      openMoodModal();
    }

    if (openModalBtn) {
      openModalBtn.addEventListener("click", function (e) {
        e.preventDefault();
        openMoodModal();
      });
    }

    if (closeModalBtn) {
      closeModalBtn.addEventListener("click", closeMoodModal);
    }

    if (modalOverlay) {
      modalOverlay.addEventListener("click", function (e) {
        if (e.target === modalOverlay) {
          closeMoodModal();
        }
      });
    }

    // Handle mood button click inside modal
    if (pillGrid) {
      pillGrid.addEventListener("click", function (e) {
        const btn = e.target.closest(".mood-pill-btn");
        if (!btn) return;

        document.querySelectorAll(".mood-pill-btn").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        const mood = btn.getAttribute("data-mood");
        resultsContainer.innerHTML = "<p style='text-align:center; color:#94a3b8; padding:30px 0;'>Finding movies matching your " + mood + " mood...</p>";

        fetch("knn_recommendations.php?mood=" + encodeURIComponent(mood))
          .then(res => res.json())
          .then(data => {
            if (!data || data.length === 0 || data.error) {
              resultsContainer.innerHTML = "<p style='text-align:center; color:#94a3b8; padding:30px 0;'>No movies found matching this mood. Try another one!</p>";
              return;
            }

            // Escape anything coming from the database before it goes
            // into innerHTML -- a movie title containing < or " would
            // otherwise break the markup.
            const esc = str => String(str == null ? "" : str).replace(
              /[&<>"']/g,
              c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c])
            );

            const moodCard = movie => {
              const badge = movie.match_percent != null
                ? `<div class="mood-match-badge">★ ${movie.match_percent}% Match</div>`
                : (movie.predicted_rating
                    ? `<div class="mood-match-badge">★ ${movie.predicted_rating} Match</div>`
                    : '');

              const reason = movie.explanation
                ? `<div class="mood-explanation-text">${esc(movie.explanation)}</div>`
                : '';

              return `
                <a href="details.php?id=${encodeURIComponent(movie.movie_id)}" class="mood-movie-card">
                  <img src="${esc(movie.poster)}" alt="${esc(movie.title)}" onerror="this.onerror=null;this.src='default.jpg';">
                  <div class="mood-movie-title">${esc(movie.title)}</div>
                  ${badge}
                  ${reason}
                </a>
              `;
            };

            // The endpoint tags each result: "top" = the five highest
            // scoring films, "more" = a daily rotating sample from the
            // rest of the pool. Older responses without the field fall
            // back to a single group.
            const top  = data.filter(m => m.section === "top");
            const more = data.filter(m => m.section === "more");

            let html = "";

            if (top.length) {
              html += `<h4 class="rec-section-heading">Best matches</h4>
                       <div class="mood-recommendations-grid">${top.map(moodCard).join("")}</div>`;
            }
            if (more.length) {
              html += `<h4 class="rec-section-heading">More to explore
                         <span class="rec-section-note">refreshes daily</span>
                       </h4>
                       <div class="mood-recommendations-grid">${more.map(moodCard).join("")}</div>`;
            }
            if (!html) {
              html = `<div class="mood-recommendations-grid">${data.map(moodCard).join("")}</div>`;
            }

            resultsContainer.innerHTML = html;
            resultsContainer.scrollTop = 0;
          })
          .catch(err => {
            console.error("Error fetching mood recommendations:", err);
            resultsContainer.innerHTML = "<p style='text-align:center; color:#f87171; padding:30px 0;'>Failed to load recommendations.</p>";
          });
      });
    }
  });
</script>