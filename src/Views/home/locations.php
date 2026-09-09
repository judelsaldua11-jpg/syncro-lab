<?php
// src/Views/home/locations.php - LAB LOCATIONS (Dynamic)

// Get active branches from database
$branches = getBranches(); // Already defined in inc/functions.php

// Build a list of unique countries for the filter dropdown
$countries = [];
$countryMap = [
    'Philippines' => 'philippines',
    'Spain' => 'spain'
];

foreach ($branches as $branch) {
    $address = $branch['address'];
    $detectedCountry = 'unknown';
    foreach ($countryMap as $countryName => $countrySlug) {
        if (stripos($address, $countryName) !== false) {
            $detectedCountry = $countrySlug;
            break;
        }
    }
    // Store country in a temporary array for later use
    $branch['_country'] = $detectedCountry;
    if (!in_array($detectedCountry, $countries) && $detectedCountry !== 'unknown') {
        $countries[] = $detectedCountry;
    }
}
?>

<!-- SECTION: LAB LOCATIONS -->
<section id="locations" class="locations-section" aria-label="Lab Locations">
    <div class="locations-container">
        <h2 class="section-title">LAB LOCATIONS</h2>

        <!-- Search & Filter Bar -->
        <div class="search-bar">
            <input type="text" id="search-location" class="search-input" placeholder="FIND A LAB" aria-label="Find a lab location">
            <div class="search-icons">
                <!-- Search Icon -->
                <button type="button" id="search-btn" class="search-icon-btn" aria-label="Search locations">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
                <!-- Filter Icon -->
                <button type="button" id="filter-btn" class="filter-icon-btn" aria-label="Filter locations">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="21" x2="4" y2="14"></line>
                        <line x1="4" y1="10" x2="4" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="3"></line>
                        <line x1="20" y1="21" x2="20" y2="16"></line>
                        <line x1="20" y1="12" x2="20" y2="3"></line>
                        <line x1="1" y1="14" x2="7" y2="14"></line>
                        <line x1="9" y1="8" x2="15" y2="8"></line>
                        <line x1="17" y1="16" x2="23" y2="16"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Filter Dropdown -->
        <div id="filter-dropdown" class="filter-dropdown" style="display: none;">
            <button class="filter-option active" data-filter="all">All Branches</button>
            <?php foreach ($countries as $countrySlug): ?>
                <button class="filter-option" data-filter="<?= $countrySlug ?>">
                    <?php
                    $displayName = ucfirst($countrySlug);
                    if ($countrySlug === 'philippines') echo '🇵🇭 Philippines';
                    elseif ($countrySlug === 'spain') echo '🇪🇸 Spain';
                    else echo ucfirst($countrySlug);
                    ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Locations List -->
        <div class="locations-list" id="locations-list">
            
            <?php if (empty($branches)): ?>
                <p style="color: var(--gray-dark); text-align: center; padding: 40px 0;">No branches available.</p>
            <?php else: ?>
                <?php foreach ($branches as $branch): ?>
                    <?php
                    $country = $branch['_country'] ?? 'unknown';
                    $mapsUrl = !empty($branch['maps_url']) ? $branch['maps_url'] : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($branch['address']);
                    ?>
                    <div class="location-card" data-branch-id="<?= $branch['id'] ?>" data-country="<?= $country ?>">
                        <div class="location-info">
                            <h3 class="location-name"><?= htmlspecialchars($branch['name']) ?></h3>
                            <p class="location-address">
                                <?= nl2br(htmlspecialchars($branch['address'])) ?>
                            </p>
                        </div>
                        <div class="location-actions">
                            <a href="<?= $mapsUrl ?>" 
                               target="_blank" 
                               class="btn btn--small btn--outline">
                                SHOW DIRECTIONS
                            </a>
                            <a href="/syncro lab/pages/booking.php?branch=<?= $branch['id'] ?>" 
                               class="btn btn--small btn--green">
                                BOOK LAB
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</section>

<!-- JavaScript for Search & Filter -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-location');
    const searchBtn = document.getElementById('search-btn');
    const filterBtn = document.getElementById('filter-btn');
    const filterDropdown = document.getElementById('filter-dropdown');
    const filterOptions = document.querySelectorAll('.filter-option');
    const locationCards = document.querySelectorAll('.location-card');
    const locationsList = document.getElementById('locations-list');

    // ============================================
    // SEARCH FUNCTION
    // ============================================
    function filterLocations() {
        const query = searchInput.value.toLowerCase().trim();
        const activeFilter = document.querySelector('.filter-option.active');
        const filterValue = activeFilter ? activeFilter.dataset.filter : 'all';

        let visibleCount = 0;

        locationCards.forEach(function(card) {
            const name = card.querySelector('.location-name').textContent.toLowerCase();
            const address = card.querySelector('.location-address').textContent.toLowerCase();
            const country = card.dataset.country || '';

            // Check search match
            const matchesSearch = query === '' || name.includes(query) || address.includes(query);

            // Check filter match
            const matchesFilter = filterValue === 'all' || country === filterValue;

            if (matchesSearch && matchesFilter) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Show "No results" message if none found
        let noResultsMsg = document.getElementById('no-results-msg');
        if (visibleCount === 0) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('p');
                noResultsMsg.id = 'no-results-msg';
                noResultsMsg.style.cssText = 'color: var(--gray-dark); text-align: center; padding: 40px 0; font-size: 18px;';
                noResultsMsg.textContent = 'No locations found. Try a different search or filter.';
                locationsList.appendChild(noResultsMsg);
            }
        } else {
            if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }
    }

    // ============================================
    // SEARCH EVENT LISTENERS
    // ============================================
    searchInput.addEventListener('input', filterLocations);
    searchBtn.addEventListener('click', function(e) {
        e.preventDefault();
        filterLocations();
    });

    // Enter key on search input
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            filterLocations();
        }
    });

    // ============================================
    // FILTER DROPDOWN
    // ============================================
    filterBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const isVisible = filterDropdown.style.display === 'block';
        filterDropdown.style.display = isVisible ? 'none' : 'block';
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!filterBtn.contains(e.target) && !filterDropdown.contains(e.target)) {
            filterDropdown.style.display = 'none';
        }
    });

    // Filter option click
    filterOptions.forEach(function(option) {
        option.addEventListener('click', function() {
            // Update active class
            filterOptions.forEach(function(opt) {
                opt.classList.remove('active');
            });
            this.classList.add('active');

            // Close dropdown
            filterDropdown.style.display = 'none';

            // Apply filter
            filterLocations();
        });
    });

    // ============================================
    // RESET ON PAGE LOAD (show all)
    // ============================================
    // Show all locations initially
    locationCards.forEach(function(card) {
        card.style.display = 'flex';
    });
});
</script>