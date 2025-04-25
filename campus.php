<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link rel="stylesheet" href="campus.css">
</head>
<body>
    <header class="header">
        <h1 class="header-title"><b>CAMPUS</b></h1>
    </header>
    <section class="links">
        <a href="add_campus.html" class="link-item">Add Campus</a>
        <a href="remove_vacancy.html" class="link-item">Remove Vacancy</a>
    </section>

    <!-- General Search Bar -->
    <section class="search-section">
        <form method="POST" action="">
            <input type="text" name="search_query" class="search-bar" placeholder="Search for your campus">
            <button type="submit" name="search_button" class="search-button">Search</button>
        </form>
    </section>

    <!-- Filters Section -->
    <section class="filters-section">
        <form class="filters-section" method="POST" action="">  
            <div class="filter-item">
                <label for="campus">Campus:</label>
                <select id="campus" name="campus" class="filter-input">
                    <option value="" disabled selected>Select your Campus</option>
                    <option value="jooust">JOOUST</option>
                    <option value="maseno">Maseno University</option>
                    <option value="pwani">Pwani University</option>
                </select> 
            </div>           
            <button type="submit" name="apply_filters" class="filter-button">Apply Filters</button>
        </form>
    </section>

    <!-- Listings Section -->
    <section class="campus-section">

        <?php
        
        // Database connection
        $conn = new mysqli("localhost", "root", "password", "housefy");

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Base SQL query
        $sql = "SELECT * FROM campus WHERE 1=1";

        // Handle Filters
        if (isset($_POST['apply_filters'])) {
            // Filter by campus
            if (!empty($_POST['campus'])) {
                $location = $conn->real_escape_string($_POST['campus']);
                $sql .= " AND campus_name = '$campus'";
            }
        }

        // Handle General Search
        if (isset($_POST['search_button'])) {
            if (!empty($_POST['search_query'])) {
                $search_query = $conn->real_escape_string($_POST['search_query']);
                $sql .= " AND (type LIKE '%$search_query%' OR location LIKE '%$search_query%')";
            }
        }

        // Execute Query
        $result = $conn->query($sql);

        // Display Campuses
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="campus">
                        <img src="' . $row["image_path"] . '" alt="Campus Image" class="campus-image">
                        <div class="campus-details">
                            <p><strong><ID:</strong> ' . $row["campus_id"] . '</p>
                            <p><strong>Campus:</strong> ' . htmlspecialchars($row["campus_name"]) . '</p>
                        </div>
                            <button type="submit" class="view-more-button">
                                <a href="homepagecopy.php?id=<?php echo $row["campus_id"]; ?><b>View Listings</b></a>
                            </button>
                    </div>';
            }
        } else {
            echo "<p>No results found.</p>";
        }

        $conn->close()
        ?>
    </section>

  <!-- <div class="news-section">
        <h2>Always in the News!</h2>
        <div class="news-container">
            <div class="news">Reality</div>
            <div class="news">The Times</div>
            <div class="news">Business News</div>
        </div>
    </div>
    -->

    <footer>
        <div class="footer-container">
            <div class="footer-top">
                <div class="social-links">
                    <a href="https://www.tiktok.com/@code.ke?_t=8ehCLMFgc1F&_r=1">TikTok</a>
                    <a href="#">Twitter</a>
                    <a href="https://instagram.com/code.ke?utm_source=qr&igshid+ZDc4ODBmNjlmNQ%3D%3D">Instagram</a>
                </div>
                <div class="footer-links">
                    <a href="codezda5@gmail.com">Contact</a>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
            <p class="footer-credit">&copy; 2025 Student Housing Finder. All Rights Reserved.</p>
        </div>
    </footer>
    <!-- <footer class="footer">
        <div class="footer-content">
            <p class="footer-text">2024 Student Housing Finder. All rights reserved.</p>
        </div>
    </footer>
    -->
</body>
</html>


