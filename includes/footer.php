<?php
// Close the main content container opened in header.php
?>
</div><!-- /.container -->

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- Chart.js from CDN for graphs -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

<!-- Script for click-based background animation -->
<script>
document.addEventListener('click', function() {
    const body = document.body;
    // Add the animation class
    body.classList.add('animate-bg');
    
    // Remove the class after the animation completes (2000ms = 2s)
    // so it can be re-triggered on the next click.
    setTimeout(() => {
        body.classList.remove('animate-bg');
    }, 2000);
});
</script>

<!-- Dark Mode Toggle Script -->
<script>
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;
    const moonIcon = darkModeToggle.querySelector('.fa-moon');
    const sunIcon = darkModeToggle.querySelector('.fa-sun');

    // Function to apply the theme
    const applyTheme = (theme) => {
        if (theme === 'dark') {
            body.classList.add('dark-mode');
            moonIcon.style.display = 'none';
            sunIcon.style.display = 'inline-block';
        } else {
            body.classList.remove('dark-mode');
            moonIcon.style.display = 'inline-block';
            sunIcon.style.display = 'none';
        }
    };

    // Check for saved theme in localStorage and apply it on page load
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);

    // Event listener for the toggle button
    darkModeToggle.addEventListener('click', function(e) {
        e.preventDefault();
        let currentTheme = body.classList.contains('dark-mode') ? 'dark' : 'light';
        let newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
        localStorage.setItem('theme', newTheme);
    });
</script>

</body>
</html>