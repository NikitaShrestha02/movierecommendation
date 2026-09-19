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
</style>

<header class="site-header">
  <div class="nav-container">
    <ul class="nav-menu">
      <li class="logo"><a href="index.php" style="padding:0;background:none;"><img src="LOGOO.png" alt="Logo"></a></li>
      <li><a href="index.php">Home</a></li>
      <li><a href="posters.php">Movies</a></li>
      <?php if (isset($_SESSION['uemail'])): ?>
        <li><a href="watched.php">Watched Movies</a></li>
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