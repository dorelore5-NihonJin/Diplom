<?php
session_start();
require_once 'config.php';

$pageTitle = 'Политика использования Cookie';
$pageDescription = 'Подробная информация о том, как мы используем файлы cookie на сайте HyperPC';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - HyperPC</title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <script>
        (function() {
            const storedTheme = localStorage.getItem('theme');
            const savedTheme = storedTheme || 'dark';
            if (!storedTheme) {
                localStorage.setItem('theme', savedTheme);
            }
            const root = document.documentElement;
            root.setAttribute('data-theme', savedTheme);
            root.style.backgroundColor = savedTheme === 'light' ? '#ffffff' : '#0f172a';
            root.style.colorScheme = savedTheme;
        })();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cookie-policy.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="cookie-policy-page">
        <div class="container">
            <div class="policy-header">
                <div class="policy-icon">
                    <i class="fas fa-cookie-bite"></i>
                </div>
                <h1>Политика использования Cookie</h1>
                <p class="policy-subtitle">Последнее обновление: <?= date('d.m.Y') ?></p>
            </div>

            <div class="policy-content">
                <section class="policy-section">
                    <h2><i class="fas fa-info-circle"></i> Что такое cookie?</h2>
                    <p>
                        Cookie (куки) — это небольшие текстовые файлы, которые сохраняются на вашем устройстве при посещении веб-сайтов. 
                        Они помогают сайту запоминать информацию о вашем визите, такую как предпочтения, язык интерфейса и другие настройки.
                    </p>
                    <p>
                        Cookie не содержат вирусов и не могут получить доступ к вашим личным файлам. Они используются исключительно для 
                        улучшения вашего опыта использования сайта.
                    </p>
                </section>

                <section class="policy-section">
                    <h2><i class="fas fa-list-check"></i> Какие cookie мы используем?</h2>
                    <p>Мы используем четыре группы cookie, чтобы сервис работал корректно и удобно:</p>

                    <div class="cookie-categories">
                        <div class="cookie-category">
                            <div class="category-header">
                                <div class="category-info">
                                    <h3><i class="fas fa-shield-halved"></i> Обязательные cookie</h3>
                                    <span class="category-badge required">Всегда активны</span>
                                </div>
                                <p class="category-description">
                                    Нужны для работы сайта, авторизации и безопасности. Их нельзя отключить.
                                </p>
                            </div>
                            <div class="cookie-list">
                                <div class="cookie-item">
                                    <strong>PHPSESSID, csrf_token</strong>
                                    <span>Сеанс пользователя и защита от подделки запросов</span>
                                </div>
                                <div class="cookie-item">
                                    <strong>cookieConsent</strong>
                                    <span>Сохранение вашего выбора по cookie</span>
                                </div>
                            </div>
                        </div>

                        <div class="cookie-category">
                            <div class="category-header">
                                <div class="category-info">
                                    <h3><i class="fas fa-sliders"></i> Функциональные cookie</h3>
                                    <label class="cookie-toggle">
                                        <input type="checkbox" id="functionalCookies" onchange="updateCookiePreferences()">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <p class="category-description">
                                    Запоминают ваши настройки и помогают восстановить работу в конфигураторе.
                                </p>
                            </div>
                            <div class="cookie-list">
                                <div class="cookie-item">
                                    <strong>theme, currentBuild, compareBuilds</strong>
                                    <span>Тема оформления, конфигурации и сравнение сборок</span>
                                </div>
                            </div>
                        </div>

                        <div class="cookie-category">
                            <div class="category-header">
                                <div class="category-info">
                                    <h3><i class="fas fa-chart-line"></i> Аналитические cookie</h3>
                                    <label class="cookie-toggle">
                                        <input type="checkbox" id="analyticsCookies" onchange="updateCookiePreferences()">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <p class="category-description">
                                    Помогают понять, как пользователи взаимодействуют с сайтом, чтобы мы могли улучшать сервис.
                                </p>
                            </div>
                            <div class="cookie-list">
                                <div class="cookie-item">
                                    <strong>_ga, _gid, _ym_*</strong>
                                    <span>Аналитика трафика и поведения пользователей</span>
                                </div>
                            </div>
                        </div>

                        <div class="cookie-category">
                            <div class="category-header">
                                <div class="category-info">
                                    <h3><i class="fas fa-bullhorn"></i> Рекламные cookie</h3>
                                    <label class="cookie-toggle">
                                        <input type="checkbox" id="marketingCookies" onchange="updateCookiePreferences()">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                <p class="category-description">
                                    Нужны для персонализации рекламных предложений и оценки эффективности кампаний.
                                </p>
                            </div>
                            <div class="cookie-list">
                                <div class="cookie-item">
                                    <strong>_fbp, ads/ga-audiences</strong>
                                    <span>Ремаркетинг и персонализация рекламы</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="policy-section">
                    <h2><i class="fas fa-gears"></i> Управление cookie</h2>
                    <p>
                        Вы можете в любой момент изменить свои предпочтения относительно использования cookie. 
                        Обратите внимание, что отключение некоторых типов cookie может повлиять на функциональность сайта.
                    </p>
                    
                    <div class="preference-actions">
                        <button type="button" class="btn-preference btn-accept-all" onclick="acceptAllCookies()">
                            <i class="fas fa-check-double"></i>
                            Принять все cookie
                        </button>
                        <button type="button" class="btn-preference btn-save" onclick="saveCookiePreferences()">
                            <i class="fas fa-floppy-disk"></i>
                            Сохранить настройки
                        </button>
                        <button type="button" class="btn-preference btn-decline-all" onclick="declineAllCookies()">
                            <i class="fas fa-ban"></i>
                            Отклонить необязательные
                        </button>
                    </div>

                    <div class="browser-settings">
                        <h3><i class="fas fa-browser"></i> Управление через браузер</h3>
                        <p>Вы также можете управлять cookie через настройки вашего браузера:</p>
                        <ul>
                            <li><strong>Google Chrome:</strong> Настройки → Конфиденциальность и безопасность → Файлы cookie</li>
                            <li><strong>Mozilla Firefox:</strong> Настройки → Приватность и защита → Куки и данные сайтов</li>
                            <li><strong>Safari:</strong> Настройки → Конфиденциальность → Управление данными веб-сайтов</li>
                            <li><strong>Microsoft Edge:</strong> Настройки → Файлы cookie и разрешения сайтов</li>
                        </ul>
                    </div>
                </section>

                <section class="policy-section">
                    <h2><i class="fas fa-clock-rotate-left"></i> Срок хранения cookie</h2>
                    <p>Различные типы cookie хранятся в течение разных периодов времени:</p>
                    <ul>
                        <li><strong>Сеансовые cookie:</strong> Удаляются автоматически при закрытии браузера</li>
                        <li><strong>Постоянные cookie:</strong> Хранятся от нескольких дней до 2 лет в зависимости от типа</li>
                        <li><strong>Согласие на cookie:</strong> Действительно в течение 6 месяцев, после чего запрашивается повторно</li>
                    </ul>
                </section>

                <section class="policy-section">
                    <h2><i class="fas fa-shield-alt"></i> Безопасность и конфиденциальность</h2>
                    <p>
                        Мы серьёзно относимся к защите ваших данных. Cookie, которые мы используем, не содержат личной информации, 
                        которая могла бы идентифицировать вас напрямую. Мы не передаём данные cookie третьим лицам, 
                        за исключением аналитических и рекламных сервисов, указанных выше.
                    </p>
                    <p>
                        Для получения дополнительной информации о том, как мы обрабатываем ваши данные, 
                        ознакомьтесь с нашей <a href="privacy.php">Политикой конфиденциальности</a>.
                    </p>
                </section>

                <section class="policy-section">
                    <h2><i class="fas fa-envelope"></i> Контакты</h2>
                    <p>
                        Если у вас есть вопросы о нашей политике использования cookie, свяжитесь с нами:
                    </p>
                    <ul>
                        <li><i class="fas fa-envelope"></i> Email: <a href="mailto:privacy@hyperpc.ru">privacy@hyperpc.ru</a></li>
                        <li><i class="fas fa-phone"></i> Телефон: +7 (495) 123-45-67</li>
                        <li><i class="fas fa-headset"></i> <a href="support.php">Форма обратной связи</a></li>
                    </ul>
                </section>
            </div>

            <div class="policy-footer">
                <p><i class="fas fa-calendar-check"></i> Эта политика была последний раз обновлена <?= date('d F Y', strtotime('2025-01-01')) ?> года.</p>
                <a href="index.php" class="btn-back-home">
                    <i class="fas fa-arrow-left"></i>
                    Вернуться на главную
                </a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script src="js/cookie-preferences.js"></script>
</body>
</html>
