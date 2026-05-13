<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Политика конфиденциальности - <?= SITE_NAME ?></title>
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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .legal-page {
            padding: 60px 0;
            min-height: calc(100vh - 160px);
        }

        .legal-container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .legal-header {
            text-align: center;
            margin-bottom: 48px;
            padding-bottom: 32px;
            border-bottom: 2px solid var(--border);
        }

        .legal-header i {
            font-size: 56px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        .legal-header h1 {
            font-size: 36px;
            font-weight: 700;
            margin: 0 0 12px;
            color: var(--text);
        }

        .legal-header .update-date {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .legal-content {
            color: var(--text);
            line-height: 1.8;
        }

        .legal-content h2 {
            font-size: 24px;
            font-weight: 700;
            margin: 40px 0 20px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .legal-content h2 i {
            color: var(--primary);
            font-size: 20px;
        }

        .legal-content h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 24px 0 12px;
            color: var(--text);
        }

        .legal-content p {
            margin: 16px 0;
            color: var(--text-secondary);
        }

        .legal-content ul, .legal-content ol {
            margin: 16px 0;
            padding-left: 24px;
        }

        .legal-content li {
            margin: 8px 0;
            color: var(--text-secondary);
        }

        .legal-content strong {
            color: var(--text);
            font-weight: 600;
        }

        .highlight-box {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid var(--primary);
            padding: 20px;
            margin: 24px 0;
            border-radius: 8px;
        }

        .highlight-box p {
            margin: 0;
            color: var(--text);
        }

        .contact-box {
            background: var(--hover-bg);
            padding: 24px;
            border-radius: 16px;
            margin-top: 40px;
        }

        .contact-box h3 {
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contact-box h3 i {
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .legal-container {
                padding: 32px 24px;
                border-radius: 16px;
            }

            .legal-header h1 {
                font-size: 28px;
            }

            .legal-content h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="legal-page">
        <div class="container">
            <div class="legal-container">
                <div class="legal-header">
                    <i class="fas fa-shield-halved"></i>
                    <h1>Политика конфиденциальности</h1>
                    <p class="update-date">Последнее обновление: 17 января 2026 года</p>
                </div>

                <div class="legal-content">
                    <div class="highlight-box">
                        <p><strong>HyperPC</strong> (далее — «Мы», «Наш сервис») серьезно относится к защите ваших персональных данных. Настоящая Политика конфиденциальности описывает, как мы собираем, используем и защищаем вашу информацию в соответствии с законодательством Российской Федерации.</p>
                    </div>

                    <h2><i class="fas fa-info-circle"></i> 1. Общие положения</h2>
                    <p>Настоящая Политика конфиденциальности применяется ко всем пользователям сервиса HyperPC и регулируется:</p>
                    <ul>
                        <li>Федеральным законом РФ № 152-ФЗ «О персональных данных»</li>
                        <li>Федеральным законом РФ № 149-ФЗ «Об информации, информационных технологиях и о защите информации»</li>
                        <li>Федеральным законом РФ № 242-ФЗ (локализация баз персональных данных)</li>
                    </ul>

                    <h2><i class="fas fa-database"></i> 2. Какие данные мы собираем</h2>
                    
                    <h3>2.1. Данные, предоставляемые вами</h3>
                    <ul>
                        <li><strong>Регистрационные данные:</strong> имя пользователя, адрес электронной почты, пароль (хранится в зашифрованном виде)</li>
                        <li><strong>Данные профиля:</strong> информация о ваших сборках ПК, сохраненные конфигурации</li>
                        <li><strong>Коммуникации:</strong> сообщения в службу поддержки, отзывы</li>
                    </ul>

                    <h3>2.2. Автоматически собираемые данные</h3>
                    <ul>
                        <li><strong>Технические данные:</strong> IP-адрес, тип браузера, операционная система</li>
                        <li><strong>Данные об использовании:</strong> страницы, которые вы посещаете, время на сайте, клики</li>
                        <li><strong>Файлы cookie:</strong> для улучшения работы сервиса и персонализации</li>
                    </ul>

                    <h2><i class="fas fa-bullseye"></i> 3. Цели использования данных</h2>
                    <p>Мы используем ваши персональные данные для следующих целей:</p>
                    <ol>
                        <li><strong>Предоставление услуг:</strong> создание и управление вашим аккаунтом, сохранение сборок ПК</li>
                        <li><strong>Улучшение сервиса:</strong> анализ использования для оптимизации функционала</li>
                        <li><strong>Безопасность:</strong> предотвращение мошенничества и несанкционированного доступа</li>
                        <li><strong>Коммуникация:</strong> отправка важных уведомлений о сервисе (с вашего согласия)</li>
                        <li><strong>Соблюдение законодательства:</strong> выполнение юридических обязательств</li>
                    </ol>

                    <h2><i class="fas fa-lock"></i> 4. Защита персональных данных</h2>
                    <p>Мы применяем современные технологии защиты информации:</p>
                    <ul>
                        <li><strong>Шифрование:</strong> все пароли хранятся с использованием алгоритма Argon2ID</li>
                        <li><strong>SSL/TLS:</strong> защищенное соединение для передачи данных</li>
                        <li><strong>Защита от атак:</strong> системы предотвращения SQL-инъекций, XSS, CSRF</li>
                        <li><strong>Ограничение доступа:</strong> только авторизованный персонал имеет доступ к данным</li>
                        <li><strong>Регулярный аудит:</strong> проверка систем безопасности</li>
                        <li><strong>Резервное копирование:</strong> регулярные бэкапы для предотвращения потери данных</li>
                    </ul>

                    <h2><i class="fas fa-share-nodes"></i> 5. Передача данных третьим лицам</h2>
                    <p>Мы <strong>не продаем</strong> ваши персональные данные третьим лицам. Передача данных возможна только в следующих случаях:</p>
                    <ul>
                        <li><strong>С вашего согласия:</strong> когда вы явно разрешаете передачу</li>
                        <li><strong>Поставщики услуг:</strong> надежные партнеры, помогающие в работе сервиса (хостинг, аналитика)</li>
                        <li><strong>Юридические требования:</strong> по запросу государственных органов РФ в соответствии с законодательством</li>
                        <li><strong>Защита прав:</strong> для предотвращения мошенничества или нарушения условий использования</li>
                    </ul>

                    <h2><i class="fas fa-cookie-bite"></i> 6. Использование файлов cookie</h2>
                    <p>Мы используем файлы cookie для:</p>
                    <ul>
                        <li>Сохранения настроек (например, темы оформления)</li>
                        <li>Поддержания сеанса авторизации</li>
                        <li>Анализа посещаемости и поведения пользователей</li>
                        <li>Улучшения функциональности сервиса</li>
                    </ul>
                    <p>Вы можете управлять cookie в настройках вашего браузера. Отключение cookie может ограничить функциональность сайта.</p>

                    <h2><i class="fas fa-user-shield"></i> 7. Ваши права</h2>
                    <p>В соответствии с законодательством Российской Федерации, вы имеете следующие права:</p>
                    <ul>
                        <li><strong>Право на доступ:</strong> запросить копию ваших персональных данных</li>
                        <li><strong>Право на исправление:</strong> обновить неточные или неполные данные</li>
                        <li><strong>Право на удаление:</strong> запросить удаление ваших данных при наличии законных оснований</li>
                        <li><strong>Право на ограничение обработки:</strong> ограничить использование ваших данных</li>
                        <li><strong>Право на возражение:</strong> отказаться от обработки данных в маркетинговых целях</li>
                        <li><strong>Право на отзыв согласия:</strong> в любое время отозвать ранее данное согласие</li>
                    </ul>

                    <h2><i class="fas fa-clock"></i> 8. Хранение данных</h2>
                    <p>Мы храним ваши персональные данные только в течение необходимого периода:</p>
                    <ul>
                        <li><strong>Активные аккаунты:</strong> данные хранятся до удаления аккаунта</li>
                        <li><strong>Неактивные аккаунты:</strong> удаляются через 3 года бездействия (с предварительным уведомлением)</li>
                        <li><strong>Логи безопасности:</strong> хранятся 1 год для обеспечения безопасности</li>
                        <li><strong>Резервные копии:</strong> удаляются в течение 90 дней после удаления основных данных</li>
                    </ul>
                    <p>Базы персональных данных пользователей из РФ хранятся на серверах, расположенных на территории Российской Федерации.</p>

                    <h2><i class="fas fa-child"></i> 9. Защита данных несовершеннолетних</h2>
                    <p>Наш сервис предназначен для лиц старше 18 лет. Мы не собираем намеренно персональные данные несовершеннолетних. Если вы являетесь родителем или законным представителем и обнаружили, что ваш ребенок предоставил нам данные, свяжитесь с нами для их удаления.</p>

                    <h2><i class="fas fa-globe"></i> 10. Международная передача данных</h2>
                    <p>Основная обработка данных производится на территории Российской Федерации. Трансграничная передача возможна только при наличии законных оснований и при обеспечении необходимого уровня защиты персональных данных.</p>

                    <h2><i class="fas fa-edit"></i> 11. Изменения в Политике конфиденциальности</h2>
                    <p>Мы можем обновлять настоящую Политику конфиденциальности. О существенных изменениях мы уведомим вас по электронной почте или через уведомление на сайте за 30 дней до вступления изменений в силу. Дата последнего обновления указана в начале документа.</p>

                    <h2><i class="fas fa-gavel"></i> 12. Применимое право и юрисдикция</h2>
                    <p>Настоящая Политика конфиденциальности регулируется законодательством Российской Федерации. Любые споры подлежат рассмотрению в судах г. Москвы.</p>

                    <div class="contact-box">
                        <h3><i class="fas fa-envelope"></i> Контактная информация</h3>
                        <p>Если у вас есть вопросы о настоящей Политике конфиденциальности или вы хотите воспользоваться своими правами, свяжитесь с нами:</p>
                        <ul>
                            <li><strong>Email:</strong> privacy@hyperpc.ru</li>
                            <li><strong>Адрес:</strong> Москва, Россия</li>
                            <li><strong>Ответственный за обработку данных:</strong> отдел по работе с персональными данными HyperPC</li>
                        </ul>
                        <p style="margin-top: 16px;"><em>Мы обязуемся ответить на ваш запрос в течение 30 дней с момента получения.</em></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
</body>
</html>
