<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link rel="stylesheet" href="Homepage1.css">
</head>
<style>
    /* Link Styling */
a {
    text-decoration: none; /* Remove underline */
    color: #FF5733; /* Vibrant orange text color */
    font-size: 16px; /* Comfortable font size */
    font-weight: bold; /* Make it stand out */
    padding: 5px 10px; /* Add padding for better readability */
    border: 2px solid #FF5733; /* Add a border matching the color */
    border-radius: 5px; /* Rounded corners */
    transition: all 0.3s ease; /* Smooth hover effects */
}

a:hover {
    background-color: #FF5733; /* Orange background on hover */
    color: white; /* White text for contrast */
    transform: scale(1.05); /* Slight zoom-in on hover */
}
</style>
<body>
    <header class="header">
        <h1 class="header-title"><b>CAMPUS CRIB</b></h1>
    </header>
    
    <section class="links">
        <a href="logout.php" class="link-item">Logout</a>
    </section>

    <!-- General Search Bar -->
    <section class="search-section">
        <form method="POST" action="">
            <input type="text" name="search_query" class="search-bar" placeholder="Search for houses">
            <button type="submit" name="search_button" class="search-button">Search</button>
        </form>
    </section>

    <!-- Filters Section -->
    <section class="filters-section">
        <form method="POST" action="">
            <div class="filter-item">
                <label for="price_range">Price Range (KES):</label>
                <input type="number" id="price_range" name="price_range" class="filter-input" placeholder="Enter max price">
            </div>
            <div class="filter-item">
                <label for="distance">Distance to Campus (KM):</label>
                <input type="number" id="distance" name="distance" class="filter-input" placeholder="Enter Distance">
            </div>
            <div class="filter-item">
                <label for="listing_name">Hostel Name:</label>
                <select id="listing_name" name="listing_name" class="filter-input">
                    <option value="" disabled selected>Select a location</option>
                    <option value="Waridi">Waridi</option>
                    <option value="Mamlaka">Mamlaka</option>
                    <option value="Jaal">Jaal</option>
                    <option value="Student Center">Student Center</option>
                    <option value="Walcon">Walcon</option>
                    <option value="Milai">Milai</option>
                    <option value="Elion">Elion</option>
                    <option value="Docker Hostels">Docker Hostels</option>
                    <option value="Manhattan">Manhattan</option>
                </select>
            </div>
            <button type="submit" name="apply_filters" class="filter-button">Apply Filters</button>
        </form>
    </section>

    <!-- Listings Section -->
    <section class="listings-section">
        <?php
        // Database connection
        $conn = new mysqli("localhost", "root", "password", "housefy");

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Base SQL query
        $sql = "SELECT * FROM listing WHERE vacant_rooms != 0";

        // Handle Filters
        if (isset($_POST['apply_filters'])) {
            if (!empty($_POST['price_range'])) {
                $price_range = intval($_POST['price_range']);
                $sql .= " AND price <= $price_range";
            }
            if (!empty($_POST['distance'])) {
                $distance = intval($_POST['distance']);
                $sql .= " AND distance <= $distance";
            }
            if (!empty($_POST['listing_name'])) {
                $listing_name = $conn->real_escape_string($_POST['listing_name']);
                $sql .= " AND listing_name = '$listing_name'";
            }
        }

        // Handle General Search
        if (isset($_POST['search_button']) && !empty($_POST['search_query'])) {
            $search_query = $conn->real_escape_string($_POST['search_query']);
            $sql .= " AND (listing_name LIKE '%$search_query%' OR listing_name LIKE '%$search_query%')";
        }

        // Sorting: Show the cheapest first
        $sql .= " ORDER BY vacant_rooms DESC";

        // Execute Query
        $result = $conn->query($sql);

        // Display Vacancies
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="listings">
                        <img src="' . htmlspecialchars($row["image_path"]) . '" alt="House Image" class="house-image">
                        <div class="listing-details">
                            <p><strong>ID:</strong> ' . htmlspecialchars($row["listing_id"]) . '</p>
                            <p><strong>Hostel Name:</strong> ' . htmlspecialchars($row["listing_name"]) . '</p>
                            <p><strong>Price:</strong> Ksh ' . htmlspecialchars($row["price"]) . '</p>
                            <p><strong>Distance to Campus:</strong> ' . htmlspecialchars($row["distance"]) . ' Km</p>
                            <p><strong>Landlord Contact:</strong> ' . htmlspecialchars($row["landlord_contact"]) . '</p>
                            <p><strong>Vacant Rooms:</strong> ' . htmlspecialchars($row["vacant_rooms"]) . '</p>
                            <p><strong>Amenities:</strong> ' . htmlspecialchars($row["description"]) . '</p>
                        </div>
                        
                            <a href="view_more_copy.php?id=' . $row["listing_id"] . '"><b>View more</b></a>
            
                    </div>';
            }
        } else {
            echo "<p>No houses match your search criteria.</p>";
        }

        $conn->close();
        ?>
    </section>

    <footer>
        <div class="footer-container">
            <div class="footer-top">
                <div class="social-links">
                    <a href="https://www.tiktok.com/@code.ke?_t=8ehCLMFgc1F&_r=1">TikTok</a>
                    <a href="#">Twitter</a>
                    <a href="https://instagram.com/code.ke?utm_source=qr&igshid=ZDc4ODBmNjlmNQ%3D%3D">Instagram</a>
                </div>
                <div class="footer-links">
                    <a href="mailto:codezda5@gmail.com">Contact</a>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
            <p class="footer-credit">&copy; 2025 Student Housing Finder. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>
