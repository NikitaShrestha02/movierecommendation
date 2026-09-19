<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-links">
            <a href="index.php">Home</a>
            <a href="posters.php">Movies</a>
            <a href="about.php">About</a>
            <a href="faqs.php">FAQs</a>
            <a href="tc.php">Terms & Conditions</a>
            <a href="contact.php">Contact</a>
        </div>
        <div class="footer-copy">
            <p>&copy; <?php echo date('Y'); ?> Movie Recommendation System. All rights reserved.</p>
        </div>
    </div>
</footer>

<style>
.site-footer {
    background-color: #111622;
    border-top: 1px solid #1e2637;
    color: #94a3b8;
    padding: 32px 24px;
    margin-top: 48px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    font-size: 14px;
}

.footer-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.footer-links {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.footer-links a {
    color: #94a3b8;
    text-decoration: none;
    transition: color 0.2s ease;
}

.footer-links a:hover {
    color: #f1f5f9;
}

.footer-copy p {
    margin: 0;
    color: #64748b;
    font-size: 13.5px;
}

@media (max-width: 640px) {
    .footer-container {
        flex-direction: column;
        text-align: center;
        gap: 14px;
    }
}
</style>
