<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h4>HyperPC</h4>
                <p>Конфигуратор и сервис заказа ПК: совместимость, FPS, подбор комплектующих и оформление сборки.</p>
                <div class="footer-cta">
                    <a href="builder.php" class="btn btn-primary btn-footer">
                        <i class="fas fa-screwdriver-wrench"></i>
                        Собрать ПК
                    </a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Информация</h4>
                <ul>
                    <li><a href="privacy.php"><i class="fas fa-shield-halved"></i> Политика конфиденциальности</a></li>
                    <li><a href="terms.php"><i class="fas fa-file-contract"></i> Условия использования</a></li>
                    <li><a href="cookie-policy.php"><i class="fas fa-cookie-bite"></i> Политика cookie</a></li>
                    <li><a href="support.php"><i class="fas fa-headset"></i> Обратная связь</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Контакты</h4>
                <ul>
                    <li><i class="fas fa-envelope"></i> info@hyperpc.ru</li>
                    <li><i class="fas fa-headset"></i> support@hyperpc.ru</li>
                    <li><i class="fas fa-location-dot"></i> Москва, Россия</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 HyperPC. Все права защищены.</p>
        </div>
    </div>
</footer>

<!-- Timezone Detection -->
<script src="js/timezone.js"></script>

<!-- Online Activity Tracking -->
<?php if (isset($_SESSION['user_id'])): ?>
<script>
    // Update user activity every 2 minutes
    function updateActivity() {
        fetch('api/update_activity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        }).catch(error => {
            console.error('Activity update error:', error);
        });
    }
    
    // Update immediately on page load
    updateActivity();
    
    // Update every 2 minutes
    setInterval(updateActivity, 120000);
    
    // Update on user interaction
    let activityTimeout;
    function scheduleActivityUpdate() {
        clearTimeout(activityTimeout);
        activityTimeout = setTimeout(updateActivity, 5000);
    }
    
    document.addEventListener('mousemove', scheduleActivityUpdate);
    document.addEventListener('keypress', scheduleActivityUpdate);
    document.addEventListener('click', scheduleActivityUpdate);
    document.addEventListener('scroll', scheduleActivityUpdate);
</script>
<?php endif; ?>

<?php include __DIR__ . '/cookie-banner.php'; ?>
