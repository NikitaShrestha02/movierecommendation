<html>
    <head>
    <style>
body, html {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: Arial, sans-serif;
  background-color: #0D0E30;
}

header {
  background-color: #0b0b3e;
  height: 60px;
  width: 100%;
  border: 2px solid white;
  display: flex;
  justify-content: center; /* center ul horizontally */
  align-items: center;
  padding: 0 40px; /* add side padding */
  box-sizing: border-box;
}

.nav-container {
  width: 100%;
  max-width: 1400px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

header ul {
  width: 100%;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between; /* spread items */
  gap: 20px; /* optional: reduces clumping */
  padding: 0;
  margin: 0;
  list-style: none;
}


header ul li {
  display: flex;
  align-items: center;
}

header ul li a {
  color: #fff;
  text-decoration: none;
  font-size: 16px;
  transition: color 0.3s ease;
}

header ul li a:hover {
  text-decoration: underline;
  color: #89c9ff;
}

.logo img {
  width: 80px;
  height: auto;
  display: block;
}

.user img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  cursor: pointer;
  transition: box-shadow 0.3s ease;
}

.user img:hover {
  box-shadow: 0 0 8px 2px #89c9ff;
}

/* Search bar styles */
#search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background-color: #1a1a5e;
  border-radius: 20px;
  padding: 2px 8px;
  height: 36px;
  border: 1px solid #444;
  min-width: 250px; /* wider for better usability */
  box-sizing: border-box;
}

#searchInput {
  padding: 6px 12px;
  font-size: 14px;
  background-color: transparent;
  border: none;
  color: white;
  width: 100%;
  outline: none;
  border-radius: 20px 0 0 20px;
}

#searchInput::placeholder {
  color: #ccc;
}

#searchButton {
  padding: 6px 14px;
  border: none;
  background-color: #007BFF;
  color: white;
  font-weight: bold;
  border-radius: 0 20px 20px 0;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.3s ease;
}

#searchButton:hover {
  background-color: #0056b3;
}

#results {
  position: absolute;
  top: 100%;
  left: 0;
  width: 100%;
  background: white;
  border: 1px solid #ccc;
  max-height: 200px;
  overflow-y: auto;
  z-index: 999;
  border-radius: 0 0 8px 8px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.result-item {
  padding: 10px;
  border-bottom: 1px solid #eee;
  cursor: pointer;
  color: black;
  font-size: 14px;
}

.result-item:hover {
  background-color: #f0f8ff;
}
        </style>
        </head>
        <body>
        <header>
            <div class="nav-container">
    <ul>
      <li class="logo"><img src="LOGOO.png"  alt="Logo">  </li>
      <li><a href="index.php">Home</a></li>
      <li><a href="posters.php">Movie</a></li>
      <?php if (isset($_SESSION['uemail'])): ?>
    <li><a href="watched.php">Watched Movies</a></li>
<?php endif; ?>

      <li class="search-bar">
        <div id="search-wrapper">
            <input type="text" id="searchInput" placeholder="Search by keyword..." />
            <button id="searchButton">Search</button>
            <div id="results"></div>
        </div>

        <script>
            function triggerMainSearch(keyword) {
                // If on index.php, update instantly via AJAX
                const mainContainer = document.getElementById('main-recommendations-container');
                if (mainContainer) {
                    fetch('fetch_recommendations.php?search=' + encodeURIComponent(keyword))
                    .then(res => res.text())
                    .then(html => {
                        mainContainer.innerHTML = html;
                    });
                } else {
                    // Not on index.php, redirect to it with search parameter
                    window.location.href = 'index.php?search=' + encodeURIComponent(keyword);
                }
            }

            document.getElementById('searchInput').addEventListener('keyup', function (e) {
                const keyword = this.value.trim();
                
                // If user presses Enter
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
        </script>
      </li>
      <li><a href="about.php">About</a></li>
      <li><a href="contact.php">Contact</a></li>
       <li class="user"><a href="userdash.php"><img src="userr.jpg"  alt="user"></a>    
    </li>
    </ul> 
        </div>
    </header>
    </body>
    </html>