-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Дек 20 2025 г., 18:28
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `jkt_pc_builder`
--

-- --------------------------------------------------------

--
-- Структура таблицы `benchmarks`
--

CREATE TABLE `benchmarks` (
  `id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `resolution` varchar(20) DEFAULT NULL,
  `settings` varchar(50) DEFAULT NULL,
  `avg_fps` int(11) DEFAULT NULL,
  `min_fps` int(11) DEFAULT NULL,
  `max_fps` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `benchmarks`
--

INSERT INTO `benchmarks` (`id`, `component_id`, `game_id`, `resolution`, `settings`, `avg_fps`, `min_fps`, `max_fps`) VALUES
(1, 7, 1, '1920x1080', 'Ultra', 180, 145, 210),
(2, 7, 1, '2560x1440', 'Ultra', 145, 120, 165),
(3, 7, 1, '3840x2160', 'Ultra', 85, 70, 95),
(4, 7, 2, '1920x1080', 'Ultra', 165, 140, 185),
(5, 7, 2, '2560x1440', 'Ultra', 120, 100, 140),
(6, 7, 2, '3840x2160', 'Ultra', 65, 55, 75),
(7, 7, 3, '1920x1080', 'High', 240, 200, 280),
(8, 7, 3, '2560x1440', 'High', 180, 150, 210),
(9, 7, 3, '3840x2160', 'High', 95, 80, 110),
(10, 7, 4, '1920x1080', 'High', 450, 380, 520),
(11, 7, 4, '2560x1440', 'High', 320, 280, 360),
(12, 8, 1, '1920x1080', 'Ultra', 155, 130, 180),
(13, 8, 1, '2560x1440', 'Ultra', 125, 105, 145),
(14, 8, 1, '3840x2160', 'Ultra', 70, 60, 80),
(15, 8, 2, '1920x1080', 'Ultra', 145, 125, 165),
(16, 8, 2, '2560x1440', 'Ultra', 105, 90, 120),
(17, 8, 2, '3840x2160', 'Ultra', 55, 48, 62),
(18, 8, 4, '1920x1080', 'High', 400, 350, 450),
(19, 9, 1, '1920x1080', 'Ultra', 135, 115, 155),
(20, 9, 1, '2560x1440', 'Ultra', 100, 85, 115),
(21, 9, 1, '3840x2160', 'High', 55, 48, 62),
(22, 9, 4, '1920x1080', 'High', 350, 300, 400),
(23, 9, 4, '2560x1440', 'High', 240, 210, 270);

-- --------------------------------------------------------

--
-- Структура таблицы `build_comments`
--

CREATE TABLE `build_comments` (
  `id` int(11) NOT NULL,
  `build_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `build_comments`
--

INSERT INTO `build_comments` (`id`, `build_id`, `user_id`, `comment`, `parent_id`, `created_at`) VALUES
(1, 1, 1, 'ку', NULL, '2025-11-27 17:50:17'),
(2, 1, 1, 'ку', NULL, '2025-11-27 17:53:01'),
(3, 1, 1, 'ку', NULL, '2025-11-27 17:54:33'),
(8, 2, 1, 'Ха', NULL, '2025-11-30 19:39:30');

-- --------------------------------------------------------

--
-- Структура таблицы `build_components`
--

CREATE TABLE `build_components` (
  `id` int(11) NOT NULL,
  `build_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `build_components`
--

INSERT INTO `build_components` (`id`, `build_id`, `component_id`, `quantity`) VALUES
(1, 1, 1, 1),
(2, 1, 179, 1),
(3, 1, 152, 1),
(4, 1, 66, 1),
(5, 1, 21, 1),
(6, 1, 122, 1),
(7, 1, 168, 1),
(8, 1, 29, 1),
(9, 2, 1, 1),
(10, 2, 8, 1),
(11, 2, 149, 1),
(12, 2, 66, 1),
(13, 2, 42, 1),
(14, 2, 169, 1),
(15, 2, 131, 1),
(79, 12, 1, 1),
(80, 12, 8, 1),
(81, 12, 149, 1),
(82, 12, 66, 1),
(83, 12, 19, 1),
(84, 12, 120, 1),
(85, 12, 42, 1),
(86, 12, 169, 1),
(87, 12, 131, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `build_likes`
--

CREATE TABLE `build_likes` (
  `id` int(11) NOT NULL,
  `build_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `build_likes`
--

INSERT INTO `build_likes` (`id`, `build_id`, `user_id`, `created_at`) VALUES
(2, 1, 1, '2025-11-24 21:57:50'),
(4, 2, 1, '2025-11-28 16:10:35'),
(5, 12, 3, '2025-11-30 18:45:05'),
(7, 2, 3, '2025-11-30 18:47:45');

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `description`, `created_at`) VALUES
(1, 'Процессоры', 'cpu', 'fa-microchip', 'Центральные процессоры Intel и AMD', '2025-11-22 17:53:06'),
(2, 'Видеокарты', 'gpu', 'fa-display', 'Графические ускорители NVIDIA и AMD', '2025-11-22 17:53:06'),
(3, 'Материнские платы', 'motherboard', 'fa-memory', 'Системные платы различных форм-факторов', '2025-11-22 17:53:06'),
(4, 'Оперативная память', 'ram', 'fa-server', 'Модули DDR4 и DDR5 памяти', '2025-11-22 17:53:06'),
(5, 'Накопители', 'storage', 'fa-hard-drive', 'SSD и HDD накопители', '2025-11-22 17:53:06'),
(6, 'Блоки питания', 'psu', 'fa-plug', 'Блоки питания различной мощности', '2025-11-22 17:53:06'),
(7, 'Корпуса', 'case', 'fa-box', 'Корпуса для ПК', '2025-11-22 17:53:06'),
(8, 'Охлаждение', 'cooling', 'fa-fan', 'Системы охлаждения процессора', '2025-11-22 17:53:06');

-- --------------------------------------------------------

--
-- Структура таблицы `components_case`
--

CREATE TABLE `components_case` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(150) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `release_year` smallint(6) DEFAULT NULL,
  `warranty_months` smallint(6) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `performance_score` int(11) DEFAULT NULL,
  `power_consumption` int(11) DEFAULT NULL,
  `mobo_form_factor` varchar(20) DEFAULT NULL,
  `case_form_factor` varchar(50) DEFAULT NULL,
  `case_max_gpu_length` int(11) DEFAULT NULL,
  `case_max_cooler_height` int(11) DEFAULT NULL,
  `case_fan_slots` tinyint(3) UNSIGNED DEFAULT NULL,
  `case_side_panel` varchar(50) DEFAULT NULL,
  `compatibility_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compatibility_info`)),
  `stock_status` enum('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
  `stock_quantity` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `tier` enum('flagship','enthusiast','mainstream','budget') DEFAULT 'mainstream',
  `dimensions_mm` varchar(50) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `noise_level` varchar(20) DEFAULT NULL,
  `efficiency_class` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `components_case`
--

INSERT INTO `components_case` (`id`, `category_id`, `name`, `manufacturer`, `model`, `price`, `release_year`, `warranty_months`, `image`, `specs`, `performance_score`, `power_consumption`, `mobo_form_factor`, `case_form_factor`, `case_max_gpu_length`, `case_max_cooler_height`, `case_fan_slots`, `case_side_panel`, `compatibility_info`, `stock_status`, `stock_quantity`, `rating`, `tier`, `dimensions_mm`, `weight_kg`, `noise_level`, `efficiency_class`, `created_at`, `updated_at`) VALUES
(25, 7, 'Lian Li O11 Dynamic EVO', 'Lian Li', 'O11 Dynamic EVO', 16990.00, 2022, 24, NULL, '{\"form_factor\": \"Mid Tower\", \"motherboard_support\": [\"E-ATX\", \"ATX\", \"Micro-ATX\", \"Mini-ITX\"], \"max_gpu_length\": \"420mm\", \"fans_included\": 0}', 9000, 0, 'E-ATX', 'Mid Tower', 422, 167, 10, 'Tempered glass', '{\"form_factor\": \"Mid Tower\", \"max_gpu_length\": 420}', 'in_stock', 15, 4.74, 'enthusiast', '465x285x459', 13.22, NULL, NULL, '2025-11-22 17:53:07', '2025-11-24 12:18:31'),
(26, 7, 'Fractal Design Meshify 2', 'Fractal Design', 'Meshify 2', 12990.00, 2021, 24, NULL, '{\"form_factor\": \"Mid Tower\", \"motherboard_support\": [\"E-ATX\", \"ATX\", \"Micro-ATX\", \"Mini-ITX\"], \"max_gpu_length\": \"400mm\", \"fans_included\": 3}', 8500, 0, 'E-ATX', 'Mid Tower', 491, 185, 9, 'Tempered glass', '{\"form_factor\": \"Mid Tower\", \"max_gpu_length\": 400}', 'in_stock', 17, 4.68, 'enthusiast', '542x240x474', 10.10, NULL, NULL, '2025-11-22 17:53:07', '2025-11-24 12:18:31'),
(27, 7, 'NZXT H7 Flow', 'NZXT', 'H7 Flow', 13990.00, 2022, 24, NULL, '{\"form_factor\": \"Mid Tower\", \"motherboard_support\": [\"ATX\", \"Micro-ATX\", \"Mini-ITX\"], \"max_gpu_length\": \"400mm\", \"fans_included\": 3}', 8500, 0, 'E-ATX', 'Mid Tower', 410, 185, 10, 'Tempered glass', '{\"form_factor\": \"Mid Tower\", \"max_gpu_length\": 400}', 'in_stock', 16, 4.61, 'mainstream', '468x244x544', 11.13, NULL, NULL, '2025-11-22 17:53:07', '2025-11-24 12:18:31'),
(43, 7, 'Phanteks NV7', 'Phanteks', 'NV7', 22990.00, 2023, 24, NULL, '{\"form_factor\":\"Full Tower\",\"motherboard_support\":[\"E-ATX\",\"ATX\",\"Micro-ATX\",\"Mini-ITX\"],\"max_gpu_length\":\"450mm\",\"fans_included\":0}', 9000, 0, 'E-ATX', 'Full Tower', 450, 185, 12, 'Tempered glass', '{\"form_factor\":\"Full Tower\",\"max_gpu_length\":450}', 'in_stock', 9, 4.65, 'enthusiast', '586x253x532', 16.90, NULL, NULL, '2025-11-22 20:38:24', '2025-11-24 12:18:31'),
(44, 7, 'Cooler Master NR200P V2', 'Cooler Master', 'NR200P V2', 12990.00, 2024, 24, NULL, '{\"form_factor\":\"Mini Tower\",\"motherboard_support\":[\"Mini-ITX\",\"Mini-DTX\"],\"max_gpu_length\":\"365mm\",\"fans_included\":3}', 8000, 0, 'Mini-ITX', 'Mini Tower', 356, 67, 7, 'Tempered glass', '{\"form_factor\":\"Mini Tower\",\"max_gpu_length\":365}', 'in_stock', 14, 4.71, 'enthusiast', '372x185x292', 5.93, NULL, NULL, '2025-11-22 20:38:24', '2025-11-24 12:18:31'),
(126, 7, 'Lian Li O11 Dynamic EVO', 'Lian Li', 'O11D EVO', 16990.00, 2021, 24, NULL, '{\"form_factor\": \"Mid Tower\", \"max_gpu_length\": \"420mm\", \"max_cooler_height\": \"167mm\", \"fan_slots\": 10, \"side_panel\": \"Glass\", \"usb_ports\": \"2x USB 3.0, 1x USB-C\"}', 9000, 0, 'E-ATX', 'Mid Tower', 422, 167, 10, 'Tempered glass', '{\"form_factor\":\"Mid Tower\",\"max_gpu_length\":420}', 'in_stock', 20, 4.95, 'flagship', '465x285x459', 13.22, NULL, NULL, '2025-11-22 22:17:07', '2025-11-24 12:18:31'),
(127, 7, 'Fractal Design Meshify 2', 'Fractal Design', 'FD-C-MES2A-01', 12990.00, 2020, 24, NULL, '{\"form_factor\": \"Mid Tower\", \"max_gpu_length\": \"360mm\", \"max_cooler_height\": \"185mm\", \"fan_slots\": 9, \"side_panel\": \"Glass\", \"usb_ports\": \"2x USB 3.0, 1x USB-C\"}', 8000, 0, 'E-ATX', 'Mid Tower', 491, 185, 9, 'Tempered glass', '{\"form_factor\":\"Mid Tower\",\"max_gpu_length\":360}', 'in_stock', 25, 4.85, 'enthusiast', '542x240x474', 10.10, NULL, NULL, '2025-11-22 22:17:07', '2025-11-24 12:18:31'),
(128, 7, 'NZXT H510 Elite', 'NZXT', 'CA-H510E-W1', 9990.00, 2019, 24, NULL, '{\"form_factor\": \"Mid Tower\", \"max_gpu_length\": \"381mm\", \"max_cooler_height\": \"165mm\", \"fan_slots\": 7, \"side_panel\": \"Glass\", \"usb_ports\": \"1x USB 3.1, 1x USB-C\"}', 8500, 0, 'ATX', 'Mid Tower', 381, 165, 4, 'Tempered glass', '{\"form_factor\":\"Mid Tower\",\"max_gpu_length\":381}', 'in_stock', 30, 4.70, 'mainstream', '428x210x460', 7.50, NULL, NULL, '2025-11-22 22:17:07', '2025-11-24 12:18:31'),
(129, 7, 'Deepcool MATREXX 55 MESH', 'Deepcool', 'DP-ATX-MATREXX55-MESH', 5990.00, 2020, 12, NULL, '{\"form_factor\": \"Mid Tower\", \"max_gpu_length\": \"370mm\", \"max_cooler_height\": \"165mm\", \"fan_slots\": 6, \"side_panel\": \"Glass\", \"usb_ports\": \"2x USB 3.0\"}', 8000, 0, 'ATX', 'Mid Tower', 380, 160, 6, 'Tempered glass', '{\"form_factor\":\"Mid Tower\",\"max_gpu_length\":370}', 'in_stock', 40, 4.50, 'budget', '460x215x480', 7.00, NULL, NULL, '2025-11-22 22:17:07', '2025-11-24 12:18:31'),
(168, 7, 'Corsair 5000D AIRFLOW', 'Corsair', 'CC-9011210-WW', 15990.00, 2021, 24, NULL, '{\"form_factor\": \"Mid Tower\", \"max_gpu_length\": \"420mm\", \"max_cooler_height\": \"170mm\", \"fan_slots\": 10, \"side_panel\": \"Glass\", \"usb_ports\": \"1x USB 3.1, 1x USB-C\"}', 9000, 0, 'E-ATX', 'Mid Tower', 420, 170, 10, 'Tempered glass', '{\"form_factor\":\"Mid Tower\",\"max_gpu_length\":420}', 'in_stock', 18, 4.88, 'enthusiast', '520x245x520', 13.84, NULL, NULL, '2025-11-23 07:09:55', '2025-11-24 12:18:31'),
(169, 7, 'be quiet! Pure Base 500DX', 'be quiet!', 'BGW37', 10990.00, 2020, 24, NULL, '{\"form_factor\": \"Mid Tower\", \"max_gpu_length\": \"369mm\", \"max_cooler_height\": \"190mm\", \"fan_slots\": 7, \"side_panel\": \"Glass\", \"usb_ports\": \"2x USB 3.0, 1x USB-C\"}', 8000, 0, 'ATX', 'Mid Tower', 369, 190, 6, 'Tempered glass', '{\"form_factor\":\"Mid Tower\",\"max_gpu_length\":369}', 'in_stock', 22, 4.78, 'mainstream', '450x232x443', 7.80, NULL, NULL, '2025-11-23 07:09:55', '2025-11-24 12:18:31'),
(170, 7, 'Phanteks Eclipse P400A', 'Phanteks', 'PH-EC400ATG', 8990.00, 2020, 24, NULL, '{\"form_factor\": \"Mid Tower\", \"max_gpu_length\": \"420mm\", \"max_cooler_height\": \"160mm\", \"fan_slots\": 7, \"side_panel\": \"Glass\", \"usb_ports\": \"2x USB 3.0\"}', 9000, 0, 'ATX', 'Mid Tower', 420, 160, 6, 'Tempered glass', '{\"form_factor\":\"Mid Tower\",\"max_gpu_length\":420}', 'in_stock', 28, 4.72, 'mainstream', '470x210x465', 7.00, NULL, NULL, '2025-11-23 07:09:55', '2025-11-24 12:18:31'),
(171, 7, 'Montech X3 MESH', 'Montech', 'X3-MESH-BK', 6990.00, 2021, 12, NULL, '{\"form_factor\": \"Mid Tower\", \"max_gpu_length\": \"380mm\", \"max_cooler_height\": \"165mm\", \"fan_slots\": 6, \"side_panel\": \"Glass\", \"usb_ports\": \"2x USB 3.0\"}', 8500, 0, 'ATX', 'Mid Tower', 305, 160, 8, 'Tempered glass', '{\"form_factor\":\"Mid Tower\",\"max_gpu_length\":380}', 'in_stock', 35, 4.58, 'budget', '370x210x480', 5.42, NULL, NULL, '2025-11-23 07:09:55', '2025-11-24 12:18:31');

-- --------------------------------------------------------

--
-- Структура таблицы `components_cooling`
--

CREATE TABLE `components_cooling` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(150) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `release_year` smallint(6) DEFAULT NULL,
  `warranty_months` smallint(6) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `performance_score` int(11) DEFAULT NULL,
  `power_consumption` int(11) DEFAULT NULL,
  `cooler_type` varchar(50) DEFAULT NULL,
  `cooler_height` int(11) DEFAULT NULL,
  `cooler_tdp` int(11) DEFAULT NULL,
  `cooler_socket` varchar(100) DEFAULT NULL,
  `compatibility_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compatibility_info`)),
  `stock_status` enum('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
  `stock_quantity` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `tier` enum('flagship','enthusiast','mainstream','budget') DEFAULT 'mainstream',
  `dimensions_mm` varchar(50) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `noise_level` varchar(20) DEFAULT NULL,
  `efficiency_class` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `components_cooling`
--

INSERT INTO `components_cooling` (`id`, `category_id`, `name`, `manufacturer`, `model`, `price`, `release_year`, `warranty_months`, `image`, `specs`, `performance_score`, `power_consumption`, `cooler_type`, `cooler_height`, `cooler_tdp`, `cooler_socket`, `compatibility_info`, `stock_status`, `stock_quantity`, `rating`, `tier`, `dimensions_mm`, `weight_kg`, `noise_level`, `efficiency_class`, `created_at`, `updated_at`) VALUES
(28, 8, 'Noctua NH-D15 chromax.black', 'Noctua', 'NH-D15', 10990.00, 2021, 72, NULL, '{\"type\": \"Air Cooler\", \"height\": \"165mm\", \"tdp\": \"250W\", \"fans\": 2, \"noise_level\": \"24.6 dBA\"}', 9000, 5, 'Air Cooler', 165, 250, 'AM4,AM5,LGA1700,LGA1200', '{\"type\": \"Air\", \"max_tdp\": 250}', 'in_stock', 28, 4.83, 'enthusiast', '150×136×165', 1.32, '24.6 dBA', NULL, '2025-11-22 17:53:07', '2025-11-23 09:11:50'),
(29, 8, 'Arctic Liquid Freezer II 360', 'Arctic', 'LF II 360', 12990.00, 2020, 72, NULL, '{\"type\": \"AIO Liquid\", \"radiator_size\": \"360mm\", \"tdp\": \"300W\", \"fans\": 3, \"noise_level\": \"22.5 dBA\"}', 9500, 10, 'AIO Liquid', NULL, 300, 'AM4,AM5,LGA1700,LGA1200', '{\"type\": \"AIO\", \"radiator\": \"360mm\", \"max_tdp\": 300}', 'in_stock', 19, 4.79, 'flagship', '398×138×38', 1.80, '22.5 dBA', NULL, '2025-11-22 17:53:07', '2025-11-23 09:11:50'),
(30, 8, 'be quiet! Dark Rock Pro 4', 'be quiet!', 'DRP4', 8990.00, 2019, 60, NULL, '{\"type\": \"Air Cooler\", \"height\": \"163mm\", \"tdp\": \"250W\", \"fans\": 2, \"noise_level\": \"24.3 dBA\"}', 9000, 5, 'Air Cooler', 163, 250, 'AM4,AM5,LGA1700,LGA1200', '{\"type\": \"Air\", \"max_tdp\": 250}', 'in_stock', 21, 4.77, 'mainstream', '145×136×163', 1.25, '24.3 dBA', NULL, '2025-11-22 17:53:07', '2025-11-23 09:11:50'),
(45, 8, 'NZXT Kraken 360 RGB', 'NZXT', 'Kraken 360', 19990.00, 2023, 72, NULL, '{\"type\":\"AIO Liquid\",\"radiator_size\":\"360mm\",\"fans\":3,\"pump\":\"7th gen Asetek\",\"max_tdp\":\"330W\"}', 9500, 8, 'AIO Liquid', NULL, 330, 'AM4,AM5,LGA1700,LGA1200', '{\"type\":\"AIO\",\"radiator\":\"360mm\",\"max_tdp\":330}', 'in_stock', 21, 4.62, 'enthusiast', '397×123×30', 1.75, '33 dBA', NULL, '2025-11-22 20:38:24', '2025-11-23 09:11:50'),
(46, 8, 'DeepCool AK620 Zero Dark', 'DeepCool', 'AK620 Zero Dark', 9990.00, 2022, 36, NULL, '{\"type\":\"Air Cooler\",\"height\":\"160mm\",\"fans\":2,\"max_tdp\":\"260W\",\"noise_level\":\"28 dBA\"}', 9000, 5, 'Air Cooler', 160, 260, 'AM4,AM5,LGA1700,LGA1200', '{\"type\":\"Air\",\"max_tdp\":260}', 'in_stock', 25, 4.79, 'mainstream', '129×138×160', 1.45, '28 dBA', NULL, '2025-11-22 20:38:24', '2025-11-23 09:11:50'),
(130, 8, 'NZXT Kraken X73 RGB', 'NZXT', 'RL-KRX73-R1', 18990.00, 2020, 72, NULL, '{\"type\": \"AIO 360mm\", \"radiator_size\": \"360mm\", \"fan_count\": 3, \"fan_size\": \"120mm\", \"pump_speed\": \"800-2800 RPM\", \"socket\": \"AM4, AM5, LGA1700, LGA1200\"}', 9500, 15, 'AIO 360mm', NULL, 300, 'AM4, AM5, LGA1700, LGA1200', '{\"type\":\"AIO\",\"radiator\":\"360mm\",\"max_tdp\":300}', 'in_stock', 22, 4.90, 'flagship', '394×120×27', 1.85, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(131, 8, 'Arctic Liquid Freezer II 280', 'Arctic', 'ACFRE00066A', 11990.00, 2020, 72, NULL, '{\"type\": \"AIO 280mm\", \"radiator_size\": \"280mm\", \"fan_count\": 2, \"fan_size\": \"140mm\", \"pump_speed\": \"800-2000 RPM\", \"socket\": \"AM4, AM5, LGA1700, LGA1200\"}', 9000, 12, 'AIO 280mm', NULL, 280, 'AM4, AM5, LGA1700, LGA1200', '{\"type\":\"AIO\",\"radiator\":\"280mm\",\"max_tdp\":280}', 'in_stock', 30, 4.85, 'enthusiast', '315×138×38', 1.45, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(132, 8, 'Noctua NH-D15', 'Noctua', 'NH-D15', 10990.00, 2014, 72, NULL, '{\"type\": \"Tower\", \"height\": \"165mm\", \"fan_count\": 2, \"fan_size\": \"140mm\", \"tdp\": \"250W\", \"socket\": \"AM4, AM5, LGA1700, LGA1200\"}', 9000, 5, 'Tower', 165, 250, 'AM4, AM5, LGA1700, LGA1200', '{\"type\":\"Air\",\"max_tdp\":250}', 'in_stock', 35, 4.95, 'enthusiast', '150×165×161', 1.32, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(133, 8, 'be quiet! Dark Rock Pro 4', 'be quiet!', 'BK022', 8990.00, 2018, 36, NULL, '{\"type\": \"Tower\", \"height\": \"163mm\", \"fan_count\": 2, \"fan_size\": \"120mm/135mm\", \"tdp\": \"250W\", \"socket\": \"AM4, AM5, LGA1700, LGA1200\"}', 9000, 4, 'Tower', 163, 250, 'AM4, AM5, LGA1700, LGA1200', '{\"type\":\"Air\",\"max_tdp\":250}', 'in_stock', 40, 4.80, 'mainstream', '136×163×146', 1.13, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(134, 8, 'Deepcool AK400', 'Deepcool', 'R-AK400-BKNNMN-G-1', 2990.00, 2021, 24, NULL, '{\"type\": \"Tower\", \"height\": \"155mm\", \"fan_count\": 1, \"fan_size\": \"120mm\", \"tdp\": \"180W\", \"socket\": \"AM4, AM5, LGA1700, LGA1200\"}', 8000, 3, 'Tower', 155, 180, 'AM4, AM5, LGA1700, LGA1200', '{\"type\":\"Air\",\"max_tdp\":180}', 'in_stock', 60, 4.60, 'budget', '127×155×97', 0.72, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(172, 8, 'Corsair iCUE H150i ELITE CAPELLIX', 'Corsair', 'CW-9060048-WW', 21990.00, 2021, 60, NULL, '{\"type\": \"AIO 360mm\", \"radiator_size\": \"360mm\", \"fan_count\": 3, \"fan_size\": \"120mm\", \"pump_speed\": \"2400 RPM\", \"socket\": \"AM4, AM5, LGA1700, LGA1200\"}', 9500, 12, 'AIO 360mm', NULL, 300, 'AM4, AM5, LGA1700, LGA1200', '{\"type\":\"AIO\",\"radiator\":\"360mm\",\"max_tdp\":300}', 'in_stock', 16, 4.85, 'flagship', '397×120×27', 1.90, '25 dBA', NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(173, 8, 'Lian Li Galahad II Trinity 280', 'Lian Li', 'GA-II-280-T', 14990.00, 2023, 72, NULL, '{\"type\": \"AIO 280mm\", \"radiator_size\": \"280mm\", \"fan_count\": 2, \"fan_size\": \"140mm\", \"pump_speed\": \"2800 RPM\", \"socket\": \"AM4, AM5, LGA1700, LGA1200\"}', 9000, 10, 'AIO 280mm', NULL, 280, 'AM4, AM5, LGA1700, LGA1200', '{\"type\":\"AIO\",\"radiator\":\"280mm\",\"max_tdp\":280}', 'in_stock', 20, 4.80, 'enthusiast', '315×140×52', 1.55, '26 dBA', NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(174, 8, 'Thermalright Peerless Assassin 120 SE', 'Thermalright', 'PA120-SE', 3990.00, 2021, 24, NULL, '{\"type\": \"Tower\", \"height\": \"157mm\", \"fan_count\": 2, \"fan_size\": \"120mm\", \"tdp\": \"220W\", \"socket\": \"AM4, AM5, LGA1700, LGA1200\"}', 8500, 4, 'Tower', 157, 220, 'AM4, AM5, LGA1700, LGA1200', '{\"type\":\"Air\",\"max_tdp\":250}', 'in_stock', 55, 4.75, 'mainstream', '127×157×104', 0.95, '25 dBA', NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(175, 8, 'ID-COOLING SE-224-XT', 'ID-COOLING', 'SE-224-XT', 1990.00, 2020, 24, NULL, '{\"type\": \"Tower\", \"height\": \"154mm\", \"fan_count\": 1, \"fan_size\": \"120mm\", \"tdp\": \"180W\", \"socket\": \"AM4, AM5, LGA1700, LGA1200\"}', 8000, 3, 'Tower', 154, 180, 'AM4, AM5, LGA1700, LGA1200', '{\"type\":\"Air\",\"max_tdp\":180}', 'in_stock', 70, 4.55, 'budget', '127×154×73', 0.58, '28 dBA', NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(176, 8, 'Cooler Master Hyper 212 Black Edition', 'Cooler Master', 'RR-212S-20PK-R1', 3990.00, 2019, 24, NULL, '{\"type\":\"Tower\",\"height\":\"159mm\",\"fan_count\":1,\"fan_size\":\"120mm\",\"tdp\":\"150W\",\"noise_level\":\"26 dBA\"}', 7800, 3, 'Tower', 159, 150, 'AM4,AM5,LGA1700,LGA1200', '{\"type\":\"Air\",\"max_tdp\":150}', 'in_stock', 50, 4.60, 'budget', '120×79×159', 0.65, '26 dBA', NULL, '2025-11-27 19:34:20', '2025-11-27 19:34:20'),
(177, 8, 'Lian Li Galahad II Trinity 360', 'Lian Li', 'GA-II-360', 17990.00, 2023, 72, NULL, '{\"type\":\"AIO 360mm\",\"radiator_size\":\"360mm\",\"fan_count\":3,\"fan_size\":\"120mm\",\"max_tdp\":\"300W\",\"noise_level\":\"30 dBA\"}', 9300, 10, 'AIO 360mm', NULL, 300, 'AM4,AM5,LGA1700,LGA1200', '{\"type\":\"AIO\",\"radiator\":\"360mm\",\"max_tdp\":300}', 'in_stock', 20, 4.80, 'enthusiast', '397×120×27', 1.85, '30 dBA', NULL, '2025-11-27 19:34:20', '2025-11-27 19:34:20');

-- --------------------------------------------------------

--
-- Структура таблицы `components_cpu`
--

CREATE TABLE `components_cpu` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(150) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `release_year` smallint(6) DEFAULT NULL,
  `warranty_months` smallint(6) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `performance_score` int(11) DEFAULT NULL,
  `power_consumption` int(11) DEFAULT NULL,
  `cpu_cores` int(11) DEFAULT NULL,
  `cpu_threads` int(11) DEFAULT NULL,
  `cpu_base_clock` decimal(4,2) DEFAULT NULL,
  `socket_type` varchar(50) DEFAULT NULL,
  `compatibility_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compatibility_info`)),
  `stock_status` enum('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
  `stock_quantity` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `tier` enum('flagship','enthusiast','mainstream','budget') DEFAULT 'mainstream',
  `dimensions_mm` varchar(50) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `noise_level` varchar(20) DEFAULT NULL,
  `efficiency_class` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `components_cpu`
--

INSERT INTO `components_cpu` (`id`, `category_id`, `name`, `manufacturer`, `model`, `price`, `release_year`, `warranty_months`, `image`, `specs`, `performance_score`, `power_consumption`, `cpu_cores`, `cpu_threads`, `cpu_base_clock`, `socket_type`, `compatibility_info`, `stock_status`, `stock_quantity`, `rating`, `tier`, `dimensions_mm`, `weight_kg`, `noise_level`, `efficiency_class`, `created_at`, `updated_at`) VALUES
(1, 1, 'Intel Core i9-14900K', 'Intel', 'i9-14900K', 52990.00, 2023, 36, NULL, '{\"cores\": 24, \"threads\": 32, \"base_clock\": \"3.2 GHz\", \"boost_clock\": \"6.0 GHz\", \"cache\": \"36 MB\", \"tdp\": \"125W\"}', 9500, 125, 24, 32, 3.20, 'LGA1700', '{\"ram_type\": [\"DDR5\", \"DDR4\"], \"chipsets\": [\"Z790\", \"B760\"]}', 'in_stock', 18, 4.90, 'flagship', NULL, 0.12, NULL, NULL, '2025-11-22 17:53:07', '2025-11-23 09:03:49'),
(2, 1, 'AMD Ryzen 9 7950X', 'AMD', 'Ryzen 9 7950X', 54990.00, 2022, 36, NULL, '{\"cores\": 16, \"threads\": 32, \"base_clock\": \"4.5 GHz\", \"boost_clock\": \"5.7 GHz\", \"cache\": \"64 MB\", \"tdp\": \"170W\"}', 9400, 170, 16, 32, 4.50, 'AM5', '{\"ram_type\": [\"DDR5\"], \"chipsets\": [\"X670\", \"B650\"]}', 'in_stock', 14, 4.88, 'flagship', NULL, 0.11, NULL, NULL, '2025-11-22 17:53:07', '2025-11-23 09:03:49'),
(3, 1, 'Intel Core i7-14700K', 'Intel', 'i7-14700K', 39990.00, 2023, 36, NULL, '{\"cores\": 20, \"threads\": 28, \"base_clock\": \"3.4 GHz\", \"boost_clock\": \"5.6 GHz\", \"cache\": \"33 MB\", \"tdp\": \"125W\"}', 8900, 125, 20, 28, 3.40, 'LGA1700', '{\"ram_type\": [\"DDR5\", \"DDR4\"], \"chipsets\": [\"Z790\", \"B760\"]}', 'in_stock', 22, 4.72, 'enthusiast', NULL, 0.10, NULL, NULL, '2025-11-22 17:53:07', '2025-11-23 09:03:49'),
(4, 1, 'AMD Ryzen 7 7800X3D', 'AMD', 'Ryzen 7 7800X3D', 42990.00, 2023, 36, NULL, '{\"cores\": 8, \"threads\": 16, \"base_clock\": \"4.2 GHz\", \"boost_clock\": \"5.0 GHz\", \"cache\": \"96 MB\", \"tdp\": \"120W\"}', 9100, 120, 8, 16, 4.20, 'AM5', '{\"ram_type\": [\"DDR5\"], \"chipsets\": [\"X670\", \"B650\"]}', 'in_stock', 20, 4.75, 'enthusiast', NULL, 0.09, NULL, NULL, '2025-11-22 17:53:07', '2025-11-23 09:03:49'),
(5, 1, 'Intel Core i5-14600K', 'Intel', 'i5-14600K', 28990.00, 2023, 36, NULL, '{\"cores\": 14, \"threads\": 20, \"base_clock\": \"3.5 GHz\", \"boost_clock\": \"5.3 GHz\", \"cache\": \"24 MB\", \"tdp\": \"125W\"}', 7800, 125, 14, 20, 3.50, 'LGA1700', '{\"ram_type\": [\"DDR5\", \"DDR4\"], \"chipsets\": [\"Z790\", \"B760\"]}', 'in_stock', 25, 4.63, 'mainstream', NULL, 0.09, NULL, NULL, '2025-11-22 17:53:07', '2025-11-23 09:03:49'),
(6, 1, 'AMD Ryzen 5 7600X', 'AMD', 'Ryzen 5 7600X', 24990.00, 2023, 36, NULL, '{\"cores\": 6, \"threads\": 12, \"base_clock\": \"4.7 GHz\", \"boost_clock\": \"5.3 GHz\", \"cache\": \"32 MB\", \"tdp\": \"105W\"}', 7500, 105, 6, 12, 4.70, 'AM5', '{\"ram_type\": [\"DDR5\"], \"chipsets\": [\"X670\", \"B650\"]}', 'in_stock', 24, 4.60, 'mainstream', NULL, 0.08, NULL, NULL, '2025-11-22 17:53:07', '2025-11-23 09:03:49'),
(31, 1, 'Intel Core i5-13400F', 'Intel', 'i5-13400F', 20990.00, 2023, 36, NULL, '{\"cores\":10,\"threads\":16,\"base_clock\":\"2.5 GHz\",\"boost_clock\":\"4.6 GHz\",\"cache\":\"20 MB\",\"tdp\":\"65W\"}', 7200, 65, 10, 16, 2.50, 'LGA1700', '{\"ram_type\":[\"DDR5\",\"DDR4\"],\"chipsets\":[\"B760\",\"H770\",\"Z790\"]}', 'in_stock', 35, 4.72, 'mainstream', NULL, 0.12, NULL, NULL, '2025-11-22 20:38:24', '2025-11-23 09:03:49'),
(32, 1, 'AMD Ryzen 5 8600G', 'AMD', 'Ryzen 5 8600G', 23990.00, 2024, 36, NULL, '{\"cores\":6,\"threads\":12,\"base_clock\":\"4.3 GHz\",\"boost_clock\":\"5.0 GHz\",\"cache\":\"22 MB\",\"graphics\":\"RDNA3 8CU\",\"tdp\":\"65W\"}', 7600, 65, 6, 12, 4.30, 'AM5', '{\"ram_type\":[\"DDR5\"],\"chipsets\":[\"A620\",\"B650\",\"X670\"]}', 'in_stock', 28, 4.68, 'mainstream', NULL, 0.10, NULL, NULL, '2025-11-22 20:38:24', '2025-11-23 09:03:49'),
(50, 1, 'Intel Core i5-13600K', 'Intel', 'i5-13600K', 28990.00, 2022, 36, NULL, '{\"cores\": 14, \"threads\": 20, \"base_clock\": \"3.5 GHz\", \"boost_clock\": \"5.1 GHz\", \"cache\": \"24MB\", \"socket\": \"LGA1700\"}', 8500, 181, 14, 20, 3.50, 'LGA1700', '{\"ram_type\":[\"DDR5\",\"DDR4\"],\"chipsets\":[\"Z790\",\"B760\",\"H770\"]}', 'in_stock', 45, 4.80, 'mainstream', '45×37.5×7', 0.06, NULL, NULL, '2025-11-22 22:14:44', '2025-11-23 09:11:50'),
(52, 1, 'Intel Core i3-12100F', 'Intel', 'i3-12100F', 8990.00, 2022, 36, NULL, '{\"cores\": 4, \"threads\": 8, \"base_clock\": \"3.3 GHz\", \"boost_clock\": \"4.3 GHz\", \"cache\": \"12MB\", \"socket\": \"LGA1700\"}', 6200, 89, 4, 8, 3.30, 'LGA1700', '{\"ram_type\":[\"DDR5\",\"DDR4\"],\"chipsets\":[\"Z790\",\"B760\",\"H770\"]}', 'in_stock', 60, 4.60, 'budget', '45×37.5×7', 0.05, NULL, NULL, '2025-11-22 22:14:44', '2025-11-23 09:11:50'),
(135, 1, 'AMD Ryzen 9 7900X', 'AMD', '7900X', 44990.00, 2022, 36, NULL, '{\"cores\": 12, \"threads\": 24, \"base_clock\": \"4.7 GHz\", \"boost_clock\": \"5.4 GHz\", \"cache\": \"64MB L3\", \"socket\": \"AM5\"}', 9200, 170, 12, 24, 4.70, 'AM5', '{\"ram_type\":[\"DDR5\"],\"chipsets\":[\"X670E\",\"X670\",\"B650\",\"A620\"]}', 'in_stock', 20, 4.82, 'flagship', NULL, NULL, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:03:49'),
(136, 1, 'Intel Core i7-13700K', 'Intel', 'i7-13700K', 36990.00, 2022, 36, NULL, '{\"cores\": 16, \"threads\": 24, \"base_clock\": \"3.4 GHz\", \"boost_clock\": \"5.4 GHz\", \"cache\": \"30MB\", \"socket\": \"LGA1700\"}', 8700, 253, 16, 24, 3.40, 'LGA1700', '{\"ram_type\":[\"DDR5\",\"DDR4\"],\"chipsets\":[\"Z790\",\"B760\",\"H770\"]}', 'in_stock', 28, 4.78, 'enthusiast', NULL, NULL, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:03:49'),
(137, 1, 'AMD Ryzen 7 7700X', 'AMD', '7700X', 32990.00, 2022, 36, NULL, '{\"cores\": 8, \"threads\": 16, \"base_clock\": \"4.5 GHz\", \"boost_clock\": \"5.4 GHz\", \"cache\": \"32MB L3\", \"socket\": \"AM5\"}', 8300, 105, 8, 16, 4.50, 'AM5', '{\"ram_type\":[\"DDR5\"],\"chipsets\":[\"X670E\",\"X670\",\"B650\",\"A620\"]}', 'in_stock', 32, 4.73, 'enthusiast', NULL, NULL, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:03:49'),
(138, 1, 'Intel Core i5-14400F', 'Intel', 'i5-14400F', 19990.00, 2024, 36, NULL, '{\"cores\": 10, \"threads\": 16, \"base_clock\": \"2.5 GHz\", \"boost_clock\": \"4.7 GHz\", \"cache\": \"20MB\", \"socket\": \"LGA1700\"}', 7300, 65, 10, 16, 2.50, 'LGA1700', '{\"ram_type\":[\"DDR5\",\"DDR4\"],\"chipsets\":[\"Z790\",\"B760\",\"H770\"]}', 'in_stock', 40, 4.68, 'mainstream', NULL, NULL, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:03:49'),
(139, 1, 'AMD Ryzen 5 5600X', 'AMD', '5600X', 14990.00, 2020, 36, NULL, '{\"cores\": 6, \"threads\": 12, \"base_clock\": \"3.7 GHz\", \"boost_clock\": \"4.6 GHz\", \"cache\": \"32MB L3\", \"socket\": \"AM4\"}', 7000, 65, 6, 12, 3.70, 'AM4', '{\"ram_type\":[\"DDR4\"],\"chipsets\":[\"B550\",\"X570\"]}', 'in_stock', 55, 4.70, 'mainstream', NULL, NULL, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:03:49'),
(140, 1, 'Intel Core i3-13100F', 'Intel', 'i3-13100F', 10990.00, 2023, 36, NULL, '{\"cores\": 4, \"threads\": 8, \"base_clock\": \"3.4 GHz\", \"boost_clock\": \"4.5 GHz\", \"cache\": \"12MB\", \"socket\": \"LGA1700\"}', 6400, 89, 4, 8, 3.40, 'LGA1700', '{\"ram_type\":[\"DDR5\",\"DDR4\"],\"chipsets\":[\"Z790\",\"B760\",\"H770\"]}', 'in_stock', 50, 4.55, 'budget', NULL, NULL, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:03:49'),
(176, 1, 'Intel Core Ultra 9 285K', 'Intel', 'Core Ultra 9 285K', 84990.00, 2024, 36, NULL, '{\"cores\":24,\"threads\":32,\"p_cores\":8,\"e_cores\":16,\"base_clock\":\"3.7 GHz\",\"boost_clock\":\"5.7 GHz\",\"cache\":\"36MB\",\"socket\":\"LGA1851\",\"tdp\":\"125W\",\"process\":\"Intel 20A / TSMC N3\"}', 9900, 125, 24, 32, 3.70, 'LGA1851', '{\"ram_type\":[\"DDR5\"],\"chipsets\":[\"Z890\",\"B860\"]}', 'in_stock', 8, 4.95, 'flagship', '45×37.5×7', 0.06, NULL, NULL, '2025-11-23 08:45:05', '2025-11-23 09:11:50'),
(177, 1, 'Intel Core Ultra 7 265K', 'Intel', 'Core Ultra 7 265K', 59990.00, 2024, 36, NULL, '{\"cores\":20,\"threads\":28,\"p_cores\":8,\"e_cores\":12,\"base_clock\":\"3.9 GHz\",\"boost_clock\":\"5.5 GHz\",\"cache\":\"33MB\",\"socket\":\"LGA1851\",\"tdp\":\"125W\",\"process\":\"Intel 20A / TSMC N3\"}', 9300, 125, 20, 28, 3.90, 'LGA1851', '{\"ram_type\":[\"DDR5\"],\"chipsets\":[\"Z890\",\"B860\"]}', 'in_stock', 12, 4.90, 'enthusiast', '45×37.5×7', 0.06, NULL, NULL, '2025-11-23 08:45:05', '2025-11-23 09:11:50'),
(178, 1, 'Intel Core Ultra 5 245K', 'Intel', 'Core Ultra 5 245K', 42990.00, 2024, 36, NULL, '{\"cores\":14,\"threads\":20,\"p_cores\":6,\"e_cores\":8,\"base_clock\":\"4.2 GHz\",\"boost_clock\":\"5.2 GHz\",\"cache\":\"24MB\",\"socket\":\"LGA1851\",\"tdp\":\"125W\",\"process\":\"Intel 20A / TSMC N3\"}', 8600, 125, 14, 20, 4.20, 'LGA1851', '{\"ram_type\":[\"DDR5\"],\"chipsets\":[\"Z890\",\"B860\",\"H810\"]}', 'in_stock', 15, 4.85, 'mainstream', '45×37.5×7', 0.06, NULL, NULL, '2025-11-23 08:45:05', '2025-11-23 09:11:50'),
(179, 1, 'AMD Ryzen 7 5700X', 'AMD', 'Ryzen 7 5700X', 21990.00, 2022, 36, NULL, '{\"cores\":8,\"threads\":16,\"base_clock\":\"3.4 GHz\",\"boost_clock\":\"4.6 GHz\",\"cache\":\"32MB L3\",\"tdp\":\"65W\"}', 8200, 65, 8, 16, 3.40, 'AM4', '{\"ram_type\":[\"DDR4\"],\"chipsets\":[\"B550\",\"X570\",\"B450\"]}', 'in_stock', 40, 4.70, 'mainstream', NULL, 0.07, NULL, NULL, '2025-11-27 19:33:49', '2025-11-27 19:33:49'),
(180, 1, 'Intel Core i5-12400F', 'Intel', 'i5-12400F', 16990.00, 2022, 36, NULL, '{\"cores\":6,\"threads\":12,\"base_clock\":\"2.5 GHz\",\"boost_clock\":\"4.4 GHz\",\"cache\":\"18MB\",\"tdp\":\"65W\"}', 7400, 65, 6, 12, 2.50, 'LGA1700', '{\"ram_type\":[\"DDR4\",\"DDR5\"],\"chipsets\":[\"B660\",\"B760\",\"Z690\"]}', 'in_stock', 60, 4.65, 'mainstream', NULL, 0.07, NULL, NULL, '2025-11-27 19:33:49', '2025-11-27 19:33:49'),
(181, 1, 'AMD Ryzen 5 5600G', 'AMD', 'Ryzen 5 5600G', 14990.00, 2021, 36, NULL, '{\"cores\":6,\"threads\":12,\"base_clock\":\"3.9 GHz\",\"boost_clock\":\"4.4 GHz\",\"cache\":\"16MB L3\",\"graphics\":\"Vega 7\",\"tdp\":\"65W\"}', 7000, 65, 6, 12, 3.90, 'AM4', '{\"ram_type\":[\"DDR4\"],\"chipsets\":[\"B550\",\"A520\",\"X570\"]}', 'in_stock', 45, 4.60, 'mainstream', NULL, 0.08, NULL, NULL, '2025-11-27 19:33:49', '2025-11-27 19:33:49'),
(182, 1, 'Intel Core i9-12900K', 'Intel', 'i9-12900K', 39990.00, 2021, 36, NULL, '{\"cores\":16,\"threads\":24,\"p_cores\":8,\"e_cores\":8,\"base_clock\":\"3.2 GHz\",\"boost_clock\":\"5.2 GHz\",\"cache\":\"30MB\",\"tdp\":\"125W\"}', 9000, 125, 16, 24, 3.20, 'LGA1700', '{\"ram_type\":[\"DDR4\",\"DDR5\"],\"chipsets\":[\"Z690\",\"Z790\",\"B660\"]}', 'in_stock', 20, 4.80, 'enthusiast', NULL, 0.07, NULL, NULL, '2025-11-27 19:33:49', '2025-11-27 19:33:49'),
(183, 1, 'AMD Ryzen 7 7700', 'AMD', 'Ryzen 7 7700', 28990.00, 2023, 36, NULL, '{\"cores\":8,\"threads\":16,\"base_clock\":\"3.8 GHz\",\"boost_clock\":\"5.3 GHz\",\"cache\":\"32MB L3\",\"tdp\":\"65W\"}', 8400, 65, 8, 16, 3.80, 'AM5', '{\"ram_type\":[\"DDR5\"],\"chipsets\":[\"B650\",\"X670\",\"A620\"]}', 'in_stock', 30, 4.75, 'enthusiast', NULL, 0.07, NULL, NULL, '2025-11-27 19:33:49', '2025-11-27 19:33:49');

-- --------------------------------------------------------

--
-- Структура таблицы `components_gpu`
--

CREATE TABLE `components_gpu` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(150) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `release_year` smallint(6) DEFAULT NULL,
  `warranty_months` smallint(6) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `performance_score` int(11) DEFAULT NULL,
  `power_consumption` int(11) DEFAULT NULL,
  `gpu_memory` int(11) DEFAULT NULL,
  `gpu_memory_type` varchar(20) DEFAULT NULL,
  `pcie_version` decimal(2,1) DEFAULT NULL,
  `compatibility_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compatibility_info`)),
  `stock_status` enum('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
  `stock_quantity` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `tier` enum('flagship','enthusiast','mainstream','budget') DEFAULT 'mainstream',
  `dimensions_mm` varchar(50) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `noise_level` varchar(20) DEFAULT NULL,
  `efficiency_class` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `components_gpu`
--

INSERT INTO `components_gpu` (`id`, `category_id`, `name`, `manufacturer`, `model`, `price`, `release_year`, `warranty_months`, `image`, `specs`, `performance_score`, `power_consumption`, `gpu_memory`, `gpu_memory_type`, `pcie_version`, `compatibility_info`, `stock_status`, `stock_quantity`, `rating`, `tier`, `dimensions_mm`, `weight_kg`, `noise_level`, `efficiency_class`, `created_at`, `updated_at`) VALUES
(7, 2, 'ASUS ROG Strix GeForce RTX 4090 OC', 'ASUS', 'RTX 4090', 159990.00, 2022, 36, NULL, '{\"memory\": \"24 GB GDDR6X\", \"core_clock\": \"2235 MHz\", \"boost_clock\": \"2520 MHz\", \"cuda_cores\": 16384, \"memory_bus\": \"384-bit\", \"tdp\": \"450W\"}', 10000, 450, 24, 'GDDR6X', 4.0, '{\"psu_min\": 850, \"pcie\": \"4.0 x16\", \"slots\": 3, \"pcie_version\": 4.0}', 'in_stock', 8, 4.95, 'flagship', '304×137×61', 2.40, '35 dBA', NULL, '2025-11-22 17:53:07', '2025-11-27 19:33:38'),
(8, 2, 'MSI Gaming X Trio GeForce RTX 4080', 'MSI', 'RTX 4080', 109990.00, 2022, 36, NULL, '{\"memory\": \"16 GB GDDR6X\", \"core_clock\": \"2210 MHz\", \"boost_clock\": \"2510 MHz\", \"cuda_cores\": 9728, \"memory_bus\": \"256-bit\", \"tdp\": \"320W\"}', 9200, 320, 16, 'GDDR6X', 4.0, '{\"psu_min\": 750, \"pcie\": \"4.0 x16\", \"slots\": 3, \"pcie_version\": 4.0}', 'in_stock', 12, 4.82, 'flagship', '304×137×61', 2.15, '34 dBA', NULL, '2025-11-22 17:53:07', '2025-11-27 19:33:38'),
(9, 2, 'Gigabyte AORUS GeForce RTX 4070 Ti Elite', 'Gigabyte', 'RTX 4070 Ti', 79990.00, 2023, 36, NULL, '{\"memory\": \"12 GB GDDR6X\", \"core_clock\": \"2310 MHz\", \"boost_clock\": \"2610 MHz\", \"cuda_cores\": 7680, \"memory_bus\": \"192-bit\", \"tdp\": \"285W\"}', 8500, 285, 12, 'GDDR6X', 4.0, '{\"psu_min\": 700, \"pcie\": \"4.0 x16\", \"slots\": 2, \"pcie_version\": 4.0}', 'in_stock', 16, 4.70, 'enthusiast', '336×140×62', 1.95, '33 dBA', NULL, '2025-11-22 17:53:07', '2025-11-27 19:33:38'),
(10, 2, 'Sapphire Nitro+ Radeon RX 7900 XTX', 'Sapphire', 'RX 7900 XTX', 89990.00, 2022, 36, NULL, '{\"memory\": \"24 GB GDDR6\", \"game_clock\": \"2300 MHz\", \"boost_clock\": \"2500 MHz\", \"stream_processors\": 6144, \"memory_bus\": \"384-bit\", \"tdp\": \"355W\"}', 8800, 355, 24, 'GDDR6', 4.0, '{\"psu_min\": 750, \"pcie\": \"4.0 x16\", \"slots\": 3, \"pcie_version\": 4.0}', 'in_stock', 15, 4.68, 'enthusiast', '287×123×52', 2.00, '34 dBA', NULL, '2025-11-22 17:53:07', '2025-11-27 19:33:38'),
(11, 2, 'MSI Ventus 2X GeForce RTX 4070', 'MSI', 'RTX 4070', 59990.00, 2023, 36, NULL, '{\"memory\": \"12 GB GDDR6X\", \"core_clock\": \"1920 MHz\", \"boost_clock\": \"2480 MHz\", \"cuda_cores\": 5888, \"memory_bus\": \"192-bit\", \"tdp\": \"200W\"}', 7800, 200, 12, 'GDDR6X', 4.0, '{\"psu_min\": 650, \"pcie\": \"4.0 x16\", \"slots\": 2, \"pcie_version\": 4.0}', 'in_stock', 18, 4.65, 'mainstream', '242×112×41', 1.85, '32 dBA', NULL, '2025-11-22 17:53:07', '2025-11-27 19:33:38'),
(12, 2, 'PowerColor Red Devil Radeon RX 7800 XT', 'PowerColor', 'RX 7800 XT', 54990.00, 2023, 36, NULL, '{\"memory\": \"16 GB GDDR6\", \"game_clock\": \"2120 MHz\", \"boost_clock\": \"2430 MHz\", \"stream_processors\": 3840, \"memory_bus\": \"256-bit\", \"tdp\": \"263W\"}', 7600, 263, 16, 'GDDR6', 4.0, '{\"psu_min\": 700, \"pcie\": \"4.0 x16\", \"slots\": 2, \"pcie_version\": 4.0}', 'in_stock', 19, 4.62, 'mainstream', '267×135×51', 1.90, '33 dBA', NULL, '2025-11-22 17:53:07', '2025-11-27 19:33:38'),
(33, 2, 'ASUS Dual GeForce RTX 4060 Ti 16GB OC', 'ASUS', 'RTX 4060 Ti', 57990.00, 2023, 36, NULL, '{\"memory\":\"16 GB GDDR6\",\"boost_clock\":\"2540 MHz\",\"cuda_cores\":4352,\"memory_bus\":\"128-bit\",\"tdp\":\"165W\"}', 6900, 165, 16, 'GDDR6', 4.0, '{\"psu_min\": 550, \"pcie\": \"4.0 x16\", \"slots\": 2, \"pcie_version\": 4.0}', 'in_stock', 22, 4.55, 'mainstream', NULL, 1.05, '30 dBA', NULL, '2025-11-22 20:38:24', '2025-11-27 19:33:38'),
(34, 2, 'Sapphire Pulse Radeon RX 7600 XT', 'Sapphire', 'RX 7600 XT', 38990.00, 2024, 36, NULL, '{\"memory\":\"16 GB GDDR6\",\"game_clock\":\"2470 MHz\",\"boost_clock\":\"2750 MHz\",\"stream_processors\":2048,\"memory_bus\":\"128-bit\",\"tdp\":\"190W\"}', 6400, 190, 16, 'GDDR6', 4.0, '{\"psu_min\": 600, \"pcie\": \"4.0 x8\", \"slots\": 2, \"pcie_version\": 4.0}', 'in_stock', 26, 4.61, 'mainstream', NULL, 0.95, '32 dBA', NULL, '2025-11-22 20:38:24', '2025-11-27 19:33:39'),
(58, 2, 'XFX Speedster SWFT 210 Radeon RX 6600', 'XFX', 'RX 6600', 24990.00, 2021, 36, NULL, '{\"memory\": \"8GB GDDR6\", \"memory_type\": \"GDDR6\", \"boost_clock\": \"2491 MHz\", \"stream_processors\": 1792, \"bus_width\": \"128-bit\"}', 6500, 132, 8, 'GDDR6', 4.0, '{\"psu_min\": 500, \"pcie\": \"4.0 x8\", \"slots\": 2, \"pcie_version\": 4.0}', 'in_stock', 40, 4.40, 'budget', '190×111×37', 0.68, NULL, NULL, '2025-11-22 22:14:44', '2025-11-27 16:09:20'),
(141, 2, 'Gigabyte Gaming OC GeForce RTX 4080 SUPER', 'Gigabyte', 'RTX 4080 SUPER', 119990.00, 2024, 36, NULL, '{\"memory\": \"16GB GDDR6X\", \"memory_type\": \"GDDR6X\", \"boost_clock\": \"2550 MHz\", \"cuda_cores\": 10240, \"bus_width\": \"256-bit\"}', 9500, 320, 16, 'GDDR6X', 4.0, '{\"psu_min\": 850, \"pcie\": \"4.0 x16\", \"slots\": 3, \"pcie_version\": 4.0}', 'in_stock', 10, 4.88, 'flagship', '304×137×61', 2.20, '34 dBA', NULL, '2025-11-23 07:09:55', '2025-11-27 16:09:20'),
(142, 2, 'ASUS TUF Gaming Radeon RX 7900 XT', 'ASUS', 'RX 7900 XT', 79990.00, 2022, 36, NULL, '{\"memory\": \"20GB GDDR6\", \"memory_type\": \"GDDR6\", \"boost_clock\": \"2400 MHz\", \"stream_processors\": 5376, \"bus_width\": \"320-bit\"}', 8600, 315, 20, 'GDDR6', 4.0, '{\"psu_min\": 850, \"pcie\": \"4.0 x16\", \"slots\": 3, \"pcie_version\": 4.0}', 'in_stock', 18, 4.72, 'enthusiast', '287×123×52', 1.85, '35 dBA', NULL, '2025-11-23 07:09:55', '2025-11-27 16:09:20'),
(143, 2, 'MSI Gaming X Slim GeForce RTX 4070 SUPER', 'MSI', 'RTX 4070 SUPER', 69990.00, 2024, 36, NULL, '{\"memory\": \"12GB GDDR6X\", \"memory_type\": \"GDDR6X\", \"boost_clock\": \"2475 MHz\", \"cuda_cores\": 7168, \"bus_width\": \"192-bit\"}', 8200, 220, 12, 'GDDR6X', 4.0, '{\"psu_min\": 650, \"pcie\": \"4.0 x16\", \"slots\": 2, \"pcie_version\": 4.0}', 'in_stock', 22, 4.78, 'enthusiast', '267×112×50', 1.75, '32 dBA', NULL, '2025-11-23 07:09:55', '2025-11-27 16:09:20'),
(144, 2, 'PowerColor Hellhound Radeon RX 7700 XT', 'PowerColor', 'RX 7700 XT', 47990.00, 2023, 36, NULL, '{\"memory\": \"12GB GDDR6\", \"memory_type\": \"GDDR6\", \"boost_clock\": \"2544 MHz\", \"stream_processors\": 3456, \"bus_width\": \"192-bit\"}', 7200, 245, 12, 'GDDR6', 4.0, '{\"psu_min\": 700, \"pcie\": \"4.0 x16\", \"slots\": 2, \"pcie_version\": 4.0}', 'in_stock', 28, 4.65, 'mainstream', '267×120×50', 1.15, '33 dBA', NULL, '2025-11-23 07:09:55', '2025-11-27 16:09:20'),
(145, 2, 'ASUS Dual GeForce RTX 4060', 'ASUS', 'RTX 4060', 34990.00, 2023, 36, NULL, '{\"memory\": \"8GB GDDR6\", \"memory_type\": \"GDDR6\", \"boost_clock\": \"2460 MHz\", \"cuda_cores\": 3072, \"bus_width\": \"128-bit\"}', 6800, 115, 8, 'GDDR6', 4.0, '{\"psu_min\": 650, \"pcie\": \"4.0 x16\", \"slots\": 2, \"pcie_version\": 4.0}', 'in_stock', 35, 4.52, 'mainstream', '244×112×40', 0.85, '30 dBA', NULL, '2025-11-23 07:09:55', '2025-11-27 16:09:20'),
(146, 2, 'Sapphire Pulse Radeon RX 6700 XT', 'Sapphire', 'RX 6700 XT', 32990.00, 2021, 36, NULL, '{\"memory\": \"12GB GDDR6\", \"memory_type\": \"GDDR6\", \"boost_clock\": \"2581 MHz\", \"stream_processors\": 2560, \"bus_width\": \"192-bit\"}', 7000, 230, 12, 'GDDR6', 4.0, '{\"psu_min\": 700, \"pcie\": \"4.0 x16\", \"slots\": 2, \"pcie_version\": 4.0}', 'in_stock', 30, 4.58, 'mainstream', '267×120×40', 1.05, '32 dBA', NULL, '2025-11-23 07:09:55', '2025-11-27 16:09:20'),
(147, 2, 'Gigabyte Eagle GeForce RTX 3060 Ti', 'Gigabyte', 'RTX 3060 Ti', 29990.00, 2020, 36, NULL, '{\"memory\": \"8GB GDDR6\", \"memory_type\": \"GDDR6\", \"boost_clock\": \"1665 MHz\", \"cuda_cores\": 4864, \"bus_width\": \"256-bit\"}', 6600, 200, 8, 'GDDR6', 4.0, '{\"psu_min\": 650, \"pcie\": \"4.0 x16\", \"slots\": 2, \"pcie_version\": 4.0}', 'in_stock', 25, 4.68, 'mainstream', '242×112×40', 0.90, '31 dBA', NULL, '2025-11-23 07:09:55', '2025-11-27 16:09:20'),
(179, 2, 'ASUS ROG Strix GeForce RTX 5090', 'ASUS', 'RTX 5090', 249990.00, 2025, 36, NULL, '{\"memory\":\"32 GB GDDR7\",\"core_clock\":\"2300 MHz\",\"boost_clock\":\"2600 MHz\",\"cuda_cores\":21760,\"memory_bus\":\"512-bit\",\"architecture\":\"Blackwell\",\"pcie\":\"5.0 x16\",\"tdp\":\"575W\"}', 11500, 575, 32, 'GDDR7', 5.0, '{\"psu_min\": 1000, \"pcie\": \"5.0 x16\", \"slots\": 3, \"pcie_version\": 5.0}', 'in_stock', 3, 4.98, 'flagship', '304×137×61', 2.50, '36 dBA', NULL, '2025-11-23 08:45:05', '2025-11-27 19:33:39'),
(180, 2, 'MSI Suprim X GeForce RTX 5080', 'MSI', 'RTX 5080', 149990.00, 2025, 36, NULL, '{\"memory\":\"16 GB GDDR7\",\"core_clock\":\"2200 MHz\",\"boost_clock\":\"2500 MHz\",\"cuda_cores\":10752,\"memory_bus\":\"256-bit\",\"architecture\":\"Blackwell\",\"pcie\":\"5.0 x16\",\"tdp\":\"360W\"}', 10300, 360, 16, 'GDDR7', 5.0, '{\"psu_min\": 850, \"pcie\": \"5.0 x16\", \"slots\": 3, \"pcie_version\": 5.0}', 'in_stock', 6, 4.90, 'enthusiast', '300×135×56', 1.90, '35 dBA', NULL, '2025-11-23 08:45:05', '2025-11-27 19:33:39'),
(181, 2, 'Gigabyte Gaming OC GeForce RTX 5070 Ti', 'Gigabyte', 'RTX 5070 Ti', 99990.00, 2025, 36, NULL, '{\"memory\":\"16 GB GDDR7\",\"core_clock\":\"2200 MHz\",\"boost_clock\":\"2500 MHz\",\"cuda_cores\":8960,\"memory_bus\":\"256-bit\",\"architecture\":\"Blackwell\",\"pcie\":\"5.0 x16\",\"tdp\":\"300W\"}', 9500, 300, 16, 'GDDR7', 5.0, '{\"psu_min\": 750, \"pcie\": \"5.0 x16\", \"slots\": 2, \"pcie_version\": 5.0}', 'in_stock', 10, 4.85, 'enthusiast', '295×130×53', 1.60, '34 dBA', NULL, '2025-11-23 08:45:05', '2025-11-27 19:33:39'),
(182, 2, 'ASUS TUF Gaming GeForce RTX 5070', 'ASUS', 'RTX 5070', 79990.00, 2025, 36, NULL, '{\"memory\":\"12 GB GDDR7\",\"core_clock\":\"2100 MHz\",\"boost_clock\":\"2500 MHz\",\"cuda_cores\":6144,\"memory_bus\":\"192-bit\",\"architecture\":\"Blackwell\",\"pcie\":\"5.0 x16\",\"tdp\":\"250W\"}', 8800, 250, 12, 'GDDR7', 5.0, '{\"psu_min\": 650, \"pcie\": \"5.0 x16\", \"slots\": 2, \"pcie_version\": 5.0}', 'in_stock', 14, 4.80, 'mainstream', '280×120×50', 1.35, '33 dBA', NULL, '2025-11-23 08:45:05', '2025-11-27 19:33:39'),
(183, 2, 'GUNNIR Arc A770 Photon 16GB', 'GUNNIR', 'Arc A770 16GB', 34990.00, 2022, 36, NULL, '{\"memory\":\"16GB GDDR6\",\"boost_clock\":\"2400 MHz\",\"xe_cores\":32,\"ray_tracing_units\":32,\"memory_bus\":\"256-bit\",\"tdp\":\"225W\"}', 7200, 225, 16, 'GDDR6', 4.0, '{\"psu_min\":650,\"pcie\":\"4.0 x16\",\"slots\":2,\"pcie_version\":4.0}', 'in_stock', 15, 4.50, 'mainstream', '280×111×42', 1.10, '34 dBA', NULL, '2025-11-27 15:57:29', '2025-11-27 16:11:36'),
(184, 2, 'GUNNIR Arc A750 Photon 8GB', 'GUNNIR', 'Arc A750 8GB', 27990.00, 2022, 36, NULL, '{\"memory\":\"8GB GDDR6\",\"boost_clock\":\"2200 MHz\",\"xe_cores\":28,\"ray_tracing_units\":28,\"memory_bus\":\"256-bit\",\"tdp\":\"225W\"}', 6800, 225, 8, 'GDDR6', 4.0, '{\"psu_min\":600,\"pcie\":\"4.0 x16\",\"slots\":2,\"pcie_version\":4.0}', 'in_stock', 20, 4.30, 'mainstream', '267×111×42', 1.00, '33 dBA', NULL, '2025-11-27 15:57:29', '2025-11-27 16:11:36'),
(185, 2, 'ASRock Challenger Arc A580 8GB', 'ASRock', 'Arc A580 8GB', 23990.00, 2023, 36, NULL, '{\"memory\":\"8GB GDDR6\",\"boost_clock\":\"2000 MHz\",\"xe_cores\":24,\"ray_tracing_units\":24,\"memory_bus\":\"256-bit\",\"tdp\":\"185W\"}', 6400, 185, 8, 'GDDR6', 4.0, '{\"psu_min\":550,\"pcie\":\"4.0 x16\",\"slots\":2,\"pcie_version\":4.0}', 'in_stock', 25, 4.20, 'mainstream', '260×111×40', 0.95, '32 dBA', NULL, '2025-11-27 15:57:29', '2025-11-27 16:11:36'),
(186, 2, 'Sapphire Pulse Radeon RX 7600', 'Sapphire', 'RX 7600', 29990.00, 2023, 36, NULL, '{\"memory\":\"8GB GDDR6\",\"game_clock\":\"2250 MHz\",\"boost_clock\":\"2655 MHz\",\"stream_processors\":2048,\"memory_bus\":\"128-bit\",\"tdp\":\"165W\"}', 6100, 165, 8, 'GDDR6', 4.0, '{\"psu_min\":550,\"pcie\":\"4.0 x8\",\"slots\":2,\"pcie_version\":4.0}', 'in_stock', 30, 4.50, 'mainstream', '240×111×40', 0.85, '32 dBA', NULL, '2025-11-27 15:57:29', '2025-11-27 16:11:36'),
(187, 2, 'PowerColor Hellhound Radeon RX 6650 XT', 'PowerColor', 'RX 6650 XT', 26990.00, 2022, 36, NULL, '{\"memory\":\"8GB GDDR6\",\"boost_clock\":\"2635 MHz\",\"stream_processors\":2048,\"memory_bus\":\"128-bit\",\"tdp\":\"180W\"}', 6000, 180, 8, 'GDDR6', 4.0, '{\"psu_min\":550,\"pcie\":\"4.0 x8\",\"slots\":2,\"pcie_version\":4.0}', 'in_stock', 25, 4.45, 'mainstream', '240×120×42', 0.90, '33 dBA', NULL, '2025-11-27 15:57:29', '2025-11-27 16:11:36'),
(188, 2, 'ASUS Dual GeForce RTX 4070 Ti SUPER', 'ASUS', 'RTX 4070 Ti SUPER', 89990.00, 2024, 36, NULL, '{\"memory\":\"16GB GDDR6X\",\"boost_clock\":\"2640 MHz\",\"cuda_cores\":8448,\"memory_bus\":\"256-bit\",\"tdp\":\"285W\"}', 8800, 285, 16, 'GDDR6X', 4.0, '{\"psu_min\":750,\"pcie\":\"4.0 x16\",\"slots\":2,\"pcie_version\":4.0}', 'in_stock', 12, 4.80, 'enthusiast', '269×135×52', 1.70, '33 dBA', NULL, '2025-11-27 19:33:54', '2025-11-27 19:33:54'),
(189, 2, 'MSI GeForce RTX 4060 Ti Gaming X 8G', 'MSI', 'RTX 4060 Ti 8G', 37990.00, 2023, 36, NULL, '{\"memory\":\"8GB GDDR6\",\"boost_clock\":\"2670 MHz\",\"cuda_cores\":4352,\"memory_bus\":\"128-bit\",\"tdp\":\"160W\"}', 6700, 160, 8, 'GDDR6', 4.0, '{\"psu_min\":550,\"pcie\":\"4.0 x16\",\"slots\":2,\"pcie_version\":4.0}', 'in_stock', 25, 4.55, 'mainstream', '247×130×42', 0.90, '30 dBA', NULL, '2025-11-27 19:33:54', '2025-11-27 19:33:54'),
(190, 2, 'Gigabyte GeForce RTX 3050 Gaming OC 8G', 'Gigabyte', 'RTX 3050', 22990.00, 2022, 36, NULL, '{\"memory\":\"8GB GDDR6\",\"boost_clock\":\"1822 MHz\",\"cuda_cores\":2560,\"memory_bus\":\"128-bit\",\"tdp\":\"130W\"}', 5200, 130, 8, 'GDDR6', 4.0, '{\"psu_min\":450,\"pcie\":\"4.0 x8\",\"slots\":2,\"pcie_version\":4.0}', 'in_stock', 35, 4.30, 'budget', '282×117×41', 0.80, '30 dBA', NULL, '2025-11-27 19:33:54', '2025-11-27 19:33:54'),
(191, 2, 'Sapphire Nitro+ Radeon RX 7800 XT', 'Sapphire', 'RX 7800 XT Nitro+', 55990.00, 2023, 36, NULL, '{\"memory\":\"16GB GDDR6\",\"game_clock\":\"2210 MHz\",\"boost_clock\":\"2520 MHz\",\"stream_processors\":3840,\"memory_bus\":\"256-bit\",\"tdp\":\"263W\"}', 7800, 263, 16, 'GDDR6', 4.0, '{\"psu_min\":700,\"pcie\":\"4.0 x16\",\"slots\":3,\"pcie_version\":4.0}', 'in_stock', 15, 4.70, 'enthusiast', '320×135×52', 1.90, '33 dBA', NULL, '2025-11-27 19:33:54', '2025-11-27 19:33:54'),
(192, 2, 'XFX Speedster MERC 310 Radeon RX 7900 XT', 'XFX', 'RX 7900 XT MERC 310', 79990.00, 2022, 36, NULL, '{\"memory\":\"20GB GDDR6\",\"game_clock\":\"2075 MHz\",\"boost_clock\":\"2500 MHz\",\"stream_processors\":5376,\"memory_bus\":\"320-bit\",\"tdp\":\"315W\"}', 8700, 315, 20, 'GDDR6', 4.0, '{\"psu_min\":850,\"pcie\":\"4.0 x16\",\"slots\":3,\"pcie_version\":4.0}', 'in_stock', 10, 4.75, 'enthusiast', '344×128×57', 2.10, '35 dBA', NULL, '2025-11-27 19:33:54', '2025-11-27 19:33:54');

-- --------------------------------------------------------

--
-- Структура таблицы `components_mobo`
--

CREATE TABLE `components_mobo` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(150) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `release_year` smallint(6) DEFAULT NULL,
  `warranty_months` smallint(6) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `performance_score` int(11) DEFAULT NULL,
  `power_consumption` int(11) DEFAULT NULL,
  `mobo_form_factor` varchar(20) DEFAULT NULL,
  `mobo_chipset` varchar(50) DEFAULT NULL,
  `mobo_ram_type` varchar(10) DEFAULT NULL,
  `mobo_max_ram_speed` int(11) DEFAULT NULL,
  `mobo_ram_slots` tinyint(3) UNSIGNED DEFAULT NULL,
  `mobo_m2_slots` tinyint(3) UNSIGNED DEFAULT NULL,
  `socket_type` varchar(50) DEFAULT NULL,
  `compatibility_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compatibility_info`)),
  `stock_status` enum('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
  `stock_quantity` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `tier` enum('flagship','enthusiast','mainstream','budget') DEFAULT 'mainstream',
  `dimensions_mm` varchar(50) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `noise_level` varchar(20) DEFAULT NULL,
  `efficiency_class` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `components_mobo`
--

INSERT INTO `components_mobo` (`id`, `category_id`, `name`, `manufacturer`, `model`, `price`, `release_year`, `warranty_months`, `image`, `specs`, `performance_score`, `power_consumption`, `mobo_form_factor`, `mobo_chipset`, `mobo_ram_type`, `mobo_max_ram_speed`, `mobo_ram_slots`, `mobo_m2_slots`, `socket_type`, `compatibility_info`, `stock_status`, `stock_quantity`, `rating`, `tier`, `dimensions_mm`, `weight_kg`, `noise_level`, `efficiency_class`, `created_at`, `updated_at`) VALUES
(13, 3, 'ASUS ROG MAXIMUS Z790 HERO', 'ASUS', 'Z790 HERO', 54990.00, 2023, 36, NULL, '{\"chipset\": \"Z790\", \"form_factor\": \"ATX\", \"ram_slots\": 4, \"max_ram\": \"128GB\", \"m2_slots\": 5, \"pcie_slots\": 3}', 9000, 75, 'ATX', 'Z790', 'DDR5', 7800, 4, 5, 'LGA1700', '{\"cpu_socket\": \"LGA1700\", \"ram_type\": [\"DDR5\"], \"max_ram_speed\": \"7800MHz\"}', 'in_stock', 10, 4.80, 'flagship', '305×244', 1.40, NULL, NULL, '2025-11-22 17:53:07', '2025-11-24 11:04:41'),
(14, 3, 'MSI MAG B650 TOMAHAWK WIFI', 'MSI', 'B650 TOMAHAWK', 24990.00, 2023, 36, NULL, '{\"chipset\": \"B650\", \"form_factor\": \"ATX\", \"ram_slots\": 4, \"max_ram\": \"128GB\", \"m2_slots\": 3, \"pcie_slots\": 2}', 7500, 60, 'ATX', 'B650', 'DDR5', 6400, 4, 3, 'AM5', '{\"cpu_socket\": \"AM5\", \"ram_type\": [\"DDR5\"], \"max_ram_speed\": \"6400MHz\"}', 'in_stock', 15, 4.66, 'mainstream', '305×244', 1.25, NULL, NULL, '2025-11-22 17:53:07', '2025-11-24 11:04:41'),
(15, 3, 'GIGABYTE X670 AORUS ELITE AX', 'GIGABYTE', 'X670 ELITE', 29990.00, 2023, 36, NULL, '{\"chipset\": \"X670\", \"form_factor\": \"ATX\", \"ram_slots\": 4, \"max_ram\": \"128GB\", \"m2_slots\": 4, \"pcie_slots\": 3}', 8000, 70, 'ATX', 'X670', 'DDR5', 6600, 4, 4, 'AM5', '{\"cpu_socket\": \"AM5\", \"ram_type\": [\"DDR5\"], \"max_ram_speed\": \"6600MHz\"}', 'in_stock', 13, 4.70, 'enthusiast', '305×244', 1.30, NULL, NULL, '2025-11-22 17:53:07', '2025-11-24 11:04:41'),
(35, 3, 'ASUS TUF GAMING B760-PLUS WIFI', 'ASUS', 'B760-PLUS WIFI', 19990.00, 2023, 36, NULL, '{\"chipset\":\"B760\",\"form_factor\":\"ATX\",\"ram_slots\":4,\"max_ram\":\"192GB\",\"m2_slots\":3,\"pcie_slots\":2}', 7500, 70, 'ATX', 'B760', 'DDR5', 7200, 4, 3, 'LGA1700', '{\"cpu_socket\":\"LGA1700\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"7200MHz\"}', 'in_stock', 18, 4.58, 'mainstream', '305×244', 1.10, NULL, NULL, '2025-11-22 20:38:24', '2025-11-24 11:04:41'),
(36, 3, 'MSI PRO X670-P WIFI', 'MSI', 'X670-P WIFI', 27990.00, 2023, 36, NULL, '{\"chipset\":\"X670\",\"form_factor\":\"ATX\",\"ram_slots\":4,\"max_ram\":\"128GB\",\"m2_slots\":4,\"pcie_slots\":3}', 8000, 75, 'ATX', 'X670', 'DDR5', 7600, 4, 4, 'AM5', '{\"cpu_socket\":\"AM5\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"7600MHz\"}', 'in_stock', 16, 4.63, 'enthusiast', '305×244', 1.25, NULL, NULL, '2025-11-22 20:38:24', '2025-11-24 11:04:41'),
(59, 3, 'ASUS ROG STRIX X670E-E', 'ASUS', 'ROG STRIX X670E-E', 42990.00, 2022, 36, NULL, '{\"form_factor\": \"ATX\", \"chipset\": \"X670E\", \"ram_slots\": 4, \"max_ram\": \"128GB\", \"m2_slots\": 4, \"pcie_slots\": 3, \"socket\": \"AM5\"}', 8000, 75, 'ATX', 'X670E', 'DDR5', 6600, 4, 4, 'AM5', '{\"cpu_socket\":\"AM5\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"6600MHz\"}', 'in_stock', 15, 4.90, 'flagship', '305×244', 1.20, NULL, NULL, '2025-11-22 22:14:44', '2025-11-24 11:04:41'),
(61, 3, 'ASUS TUF GAMING Z790-PLUS', 'ASUS', 'TUF Z790-PLUS', 28990.00, 2022, 36, NULL, '{\"form_factor\": \"ATX\", \"chipset\": \"Z790\", \"ram_slots\": 4, \"max_ram\": \"192GB\", \"m2_slots\": 4, \"pcie_slots\": 3, \"socket\": \"LGA1700\"}', 9000, 80, 'ATX', 'Z790', 'DDR5', 7200, 4, 4, 'LGA1700', '{\"cpu_socket\":\"LGA1700\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"7200MHz\"}', 'in_stock', 22, 4.80, 'enthusiast', '305×244', 1.15, NULL, NULL, '2025-11-22 22:14:44', '2025-11-24 11:04:41'),
(62, 3, 'Gigabyte B760M DS3H', 'Gigabyte', 'B760M DS3H', 12990.00, 2023, 24, NULL, '{\"form_factor\": \"Micro-ATX\", \"chipset\": \"B760\", \"ram_slots\": 4, \"max_ram\": \"128GB\", \"m2_slots\": 2, \"pcie_slots\": 2, \"socket\": \"LGA1700\"}', 7500, 60, 'Micro-ATX', 'B760', 'DDR5', 7200, 4, 2, 'LGA1700', '{\"cpu_socket\":\"LGA1700\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"7200MHz\"}', 'in_stock', 35, 4.50, 'budget', '244×244', 0.75, NULL, NULL, '2025-11-22 22:14:44', '2025-11-24 11:04:41'),
(148, 3, 'MSI MPG X670E CARBON WIFI', 'MSI', 'X670E CARBON', 36990.00, 2022, 36, NULL, '{\"form_factor\": \"ATX\", \"chipset\": \"X670E\", \"ram_slots\": 4, \"max_ram\": \"128GB\", \"m2_slots\": 4, \"pcie_slots\": 3, \"socket\": \"AM5\"}', 8000, 75, 'ATX', 'X670E', 'DDR5', 7800, 4, 4, 'AM5', '{\"cpu_socket\":\"AM5\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"7800MHz\"}', 'in_stock', 12, 4.85, 'flagship', '305×244', 1.35, NULL, NULL, '2025-11-23 07:09:55', '2025-11-24 11:04:41'),
(149, 3, 'ASUS PRIME Z790-P WIFI', 'ASUS', 'Z790-P WIFI', 22990.00, 2022, 36, NULL, '{\"form_factor\": \"ATX\", \"chipset\": \"Z790\", \"ram_slots\": 4, \"max_ram\": \"192GB\", \"m2_slots\": 3, \"pcie_slots\": 2, \"socket\": \"LGA1700\"}', 9000, 70, 'ATX', 'Z790', 'DDR5', 7200, 4, 3, 'LGA1700', '{\"cpu_socket\":\"LGA1700\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"7200MHz\"}', 'in_stock', 20, 4.72, 'enthusiast', '305×244', 1.08, NULL, NULL, '2025-11-23 07:09:55', '2025-11-24 11:04:41'),
(150, 3, 'Gigabyte X670 GAMING X AX', 'Gigabyte', 'X670 GAMING X', 24990.00, 2023, 36, NULL, '{\"form_factor\": \"ATX\", \"chipset\": \"X670\", \"ram_slots\": 4, \"max_ram\": \"128GB\", \"m2_slots\": 3, \"pcie_slots\": 2, \"socket\": \"AM5\"}', 8000, 70, 'ATX', 'X670', 'DDR5', 6400, 4, 4, 'AM5', '{\"cpu_socket\":\"AM5\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"6400MHz\"}', 'in_stock', 18, 4.68, 'enthusiast', '305×244', 1.20, NULL, NULL, '2025-11-23 07:09:55', '2025-11-24 11:04:41'),
(151, 3, 'ASRock B650M Pro RS', 'ASRock', 'B650M Pro RS', 14990.00, 2023, 24, NULL, '{\"form_factor\": \"Micro-ATX\", \"chipset\": \"B650\", \"ram_slots\": 4, \"max_ram\": \"128GB\", \"m2_slots\": 2, \"pcie_slots\": 2, \"socket\": \"AM5\"}', 7500, 60, 'Micro-ATX', 'B650', 'DDR5', 6400, 4, 2, 'AM5', '{\"cpu_socket\":\"AM5\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"6400MHz\"}', 'in_stock', 25, 4.55, 'mainstream', '244×244', 0.85, NULL, NULL, '2025-11-23 07:09:55', '2025-11-24 11:04:41'),
(152, 3, 'MSI PRO B760M-A WIFI', 'MSI', 'B760M-A WIFI', 16990.00, 2023, 24, NULL, '{\"form_factor\": \"Micro-ATX\", \"chipset\": \"B760\", \"ram_slots\": 4, \"max_ram\": \"192GB\", \"m2_slots\": 2, \"pcie_slots\": 2, \"socket\": \"LGA1700\"}', 7500, 60, 'Micro-ATX', 'B760', 'DDR5', 7200, 4, 2, 'LGA1700', '{\"cpu_socket\":\"LGA1700\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"7200MHz\"}', 'in_stock', 28, 4.60, 'mainstream', '244×244', 0.80, NULL, NULL, '2025-11-23 07:09:55', '2025-11-24 11:04:41'),
(153, 3, 'ASUS ROG STRIX B650E-F GAMING WIFI', 'ASUS', 'B650E-F GAMING WIFI', 28990.00, 2022, 36, NULL, '{\"chipset\":\"B650E\",\"form_factor\":\"ATX\",\"ram_slots\":4,\"max_ram\":\"128GB\",\"m2_slots\":3,\"pcie_slots\":3}', 8000, 70, 'ATX', 'B650E', 'DDR5', 7600, 4, 3, 'AM5', '{\"cpu_socket\":\"AM5\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"7600MHz\"}', 'in_stock', 15, 4.80, 'enthusiast', '305×244', 1.20, NULL, NULL, '2025-11-27 19:33:59', '2025-11-27 19:33:59'),
(154, 3, 'MSI MAG B760M MORTAR WIFI', 'MSI', 'B760M MORTAR WIFI', 17990.00, 2023, 36, NULL, '{\"chipset\":\"B760\",\"form_factor\":\"Micro-ATX\",\"ram_slots\":4,\"max_ram\":\"192GB\",\"m2_slots\":2,\"pcie_slots\":2}', 7600, 65, 'Micro-ATX', 'B760', 'DDR5', 7200, 4, 2, 'LGA1700', '{\"cpu_socket\":\"LGA1700\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"7200MHz\"}', 'in_stock', 30, 4.70, 'mainstream', '244×244', 0.90, NULL, NULL, '2025-11-27 19:33:59', '2025-11-27 19:33:59'),
(155, 3, 'Gigabyte Z790 AORUS MASTER', 'Gigabyte', 'Z790 AORUS MASTER', 56990.00, 2022, 36, NULL, '{\"chipset\":\"Z790\",\"form_factor\":\"ATX\",\"ram_slots\":4,\"max_ram\":\"192GB\",\"m2_slots\":5,\"pcie_slots\":3}', 9000, 80, 'ATX', 'Z790', 'DDR5', 8000, 4, 5, 'LGA1700', '{\"cpu_socket\":\"LGA1700\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"8000MHz\"}', 'in_stock', 8, 4.90, 'flagship', '305×244', 1.40, NULL, NULL, '2025-11-27 19:33:59', '2025-11-27 19:33:59'),
(156, 3, 'ASRock B550 Steel Legend', 'ASRock', 'B550 Steel Legend', 13990.00, 2020, 36, NULL, '{\"chipset\":\"B550\",\"form_factor\":\"ATX\",\"ram_slots\":4,\"max_ram\":\"128GB\",\"m2_slots\":2,\"pcie_slots\":3}', 7200, 60, 'ATX', 'B550', 'DDR4', 4733, 4, 2, 'AM4', '{\"cpu_socket\":\"AM4\",\"ram_type\":[\"DDR4\"],\"max_ram_speed\":\"4733MHz\"}', 'in_stock', 25, 4.60, 'mainstream', '305×244', 1.05, NULL, NULL, '2025-11-27 19:33:59', '2025-11-27 19:33:59'),
(157, 3, 'ASUS PRIME B650M-A AX', 'ASUS', 'B650M-A AX', 16990.00, 2022, 36, NULL, '{\"chipset\":\"B650\",\"form_factor\":\"Micro-ATX\",\"ram_slots\":4,\"max_ram\":\"128GB\",\"m2_slots\":2,\"pcie_slots\":2}', 7400, 60, 'Micro-ATX', 'B650', 'DDR5', 6400, 4, 2, 'AM5', '{\"cpu_socket\":\"AM5\",\"ram_type\":[\"DDR5\"],\"max_ram_speed\":\"6400MHz\"}', 'in_stock', 35, 4.55, 'mainstream', '244×244', 0.85, NULL, NULL, '2025-11-27 19:33:59', '2025-11-27 19:33:59');

-- --------------------------------------------------------

--
-- Структура таблицы `components_psu`
--

CREATE TABLE `components_psu` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(150) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `release_year` smallint(6) DEFAULT NULL,
  `warranty_months` smallint(6) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `performance_score` int(11) DEFAULT NULL,
  `power_consumption` int(11) DEFAULT NULL,
  `psu_wattage` int(11) DEFAULT NULL,
  `psu_efficiency` varchar(30) DEFAULT NULL,
  `compatibility_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compatibility_info`)),
  `stock_status` enum('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
  `stock_quantity` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `tier` enum('flagship','enthusiast','mainstream','budget') DEFAULT 'mainstream',
  `dimensions_mm` varchar(50) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `noise_level` varchar(20) DEFAULT NULL,
  `efficiency_class` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `components_psu`
--

INSERT INTO `components_psu` (`id`, `category_id`, `name`, `manufacturer`, `model`, `price`, `release_year`, `warranty_months`, `image`, `specs`, `performance_score`, `power_consumption`, `psu_wattage`, `psu_efficiency`, `compatibility_info`, `stock_status`, `stock_quantity`, `rating`, `tier`, `dimensions_mm`, `weight_kg`, `noise_level`, `efficiency_class`, `created_at`, `updated_at`) VALUES
(22, 6, 'Corsair RM1000x 1000W 80+ Gold', 'Corsair', 'RM1000x', 17990.00, 2022, 120, NULL, '{\"wattage\": 1000, \"efficiency\": \"80+ Gold\", \"modular\": \"Fully Modular\", \"pcie_connectors\": 6}', 9500, 0, 1000, '80+ Gold', '{\"wattage\": 1000, \"efficiency\": \"80+ Gold\"}', 'in_stock', 20, 4.76, 'enthusiast', '180×150×86', 1.90, '25 dBA', '80+ Gold', '2025-11-22 17:53:07', '2025-11-23 09:11:50'),
(23, 6, 'Seasonic FOCUS GX-850 850W 80+ Gold', 'Seasonic', 'GX-850', 13990.00, 2021, 120, NULL, '{\"wattage\": 850, \"efficiency\": \"80+ Gold\", \"modular\": \"Fully Modular\", \"pcie_connectors\": 4}', 9000, 0, 850, '80+ Gold', '{\"wattage\": 850, \"efficiency\": \"80+ Gold\"}', 'in_stock', 24, 4.69, 'mainstream', '140×150×86', 1.75, '26 dBA', '80+ Gold', '2025-11-22 17:53:07', '2025-11-23 09:11:50'),
(24, 6, 'be quiet! Straight Power 11 750W 80+ Gold', 'be quiet!', 'SP11-750', 11990.00, 2020, 120, NULL, '{\"wattage\": 750, \"efficiency\": \"80+ Gold\", \"modular\": \"Fully Modular\", \"pcie_connectors\": 4}', 8500, 0, 750, '80+ Gold', '{\"wattage\": 750, \"efficiency\": \"80+ Gold\"}', 'in_stock', 18, 4.65, 'mainstream', '160×150×86', 1.80, '24 dBA', '80+ Gold', '2025-11-22 17:53:07', '2025-11-23 09:11:50'),
(41, 6, 'MSI MPG A850G PCIE5', 'MSI', 'A850G PCIE5', 16390.00, 2023, 72, NULL, '{\"wattage\":850,\"efficiency\":\"80+ Gold\",\"modular\":\"Fully Modular\",\"pcie\":4,\"pcie5_12vhpwr\":1}', 9000, 0, 850, '80+ Gold', '{\"wattage\":850,\"eps_connectors\":2}', 'in_stock', 17, 4.74, 'enthusiast', '160×150×86', 1.85, '26 dBA', '80+ Gold', '2025-11-22 20:38:24', '2025-11-23 09:11:50'),
(42, 6, 'ASUS ROG STRIX 1000W Gold Aura Edition', 'ASUS', 'ROG-STRIX-1000G', 22990.00, 2023, 120, NULL, '{\"wattage\":1000,\"efficiency\":\"80+ Gold\",\"modular\":\"Fully Modular\",\"pcie5_12vhpwr\":2,\"fan\":\"Axial-tech 135mm\"}', 9500, 0, 1000, '80+ Gold', '{\"wattage\":1000,\"eps_connectors\":2}', 'in_stock', 12, 4.87, 'flagship', '190×150×86', 2.10, '23 dBA', '80+ Gold', '2025-11-22 20:38:24', '2025-11-23 09:11:50'),
(122, 6, 'Corsair RM1000x', 'Corsair', 'CP-9020201-EU', 16990.00, 2021, 120, NULL, '{\"wattage\": \"1000W\", \"efficiency\": \"80+ Gold\", \"modular\": \"Full\", \"fan_size\": \"135mm\", \"pfc\": \"Active\"}', 9500, 0, 1000, '80+ Gold', '{\"wattage\":1000,\"efficiency\":\"80+ Gold\"}', 'in_stock', 25, 4.90, 'flagship', '160×150×86', 1.95, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(123, 6, 'be quiet! Straight Power 11 850W', 'be quiet!', 'BN284', 13990.00, 2020, 60, NULL, '{\"wattage\": \"850W\", \"efficiency\": \"80+ Platinum\", \"modular\": \"Full\", \"fan_size\": \"135mm\", \"pfc\": \"Active\"}', 9000, 0, 850, '80+ Platinum', '{\"wattage\":850,\"efficiency\":\"80+ Platinum\"}', 'in_stock', 30, 4.85, 'enthusiast', '160×150×86', 1.76, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(124, 6, 'Seasonic FOCUS GX-750', 'Seasonic', 'FOCUS-GX-750', 10990.00, 2021, 120, NULL, '{\"wattage\": \"750W\", \"efficiency\": \"80+ Gold\", \"modular\": \"Full\", \"fan_size\": \"120mm\", \"pfc\": \"Active\"}', 8500, 0, 750, '80+ Gold', '{\"wattage\":750,\"efficiency\":\"80+ Gold\"}', 'in_stock', 35, 4.80, 'mainstream', '160×150×86', 1.60, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(125, 6, 'Thermaltake Smart RGB 600W', 'Thermaltake', 'PS-SPR-0600NHSAWE-1', 5990.00, 2020, 24, NULL, '{\"wattage\": \"600W\", \"efficiency\": \"80+ Bronze\", \"modular\": \"Non-modular\", \"fan_size\": \"120mm\", \"pfc\": \"Active\"}', 7500, 0, 600, '80+ Bronze', '{\"wattage\":600,\"efficiency\":\"80+ Bronze\"}', 'in_stock', 50, 4.40, 'budget', '150×140×86', 1.35, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(164, 6, 'EVGA SuperNOVA 1000 G6', 'EVGA', '220-G6-1000-X1', 19990.00, 2021, 120, NULL, '{\"wattage\": \"1000W\", \"efficiency\": \"80+ Gold\", \"modular\": \"Full\", \"fan_size\": \"135mm\", \"pfc\": \"Active\"}', 9500, 0, 1000, '80+ Gold', '{\"wattage\":1000,\"efficiency\":\"80+ Gold\"}', 'in_stock', 15, 4.82, 'flagship', '180×150×86', 2.05, '24 dBA', '80+ Gold', '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(165, 6, 'Cooler Master V850 SFX Gold', 'Cooler Master', 'MPY-8501-SFHAGV-EU', 14990.00, 2020, 120, NULL, '{\"wattage\": \"850W\", \"efficiency\": \"80+ Gold\", \"modular\": \"Full\", \"fan_size\": \"92mm\", \"pfc\": \"Active\"}', 9000, 0, 850, '80+ Gold', '{\"wattage\":850,\"efficiency\":\"80+ Gold\"}', 'in_stock', 20, 4.75, 'enthusiast', '125×125×63.5', 1.25, '27 dBA', '80+ Gold', '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(166, 6, 'Corsair RM750e', 'Corsair', 'CP-9020262-EU', 9990.00, 2023, 120, NULL, '{\"wattage\": \"750W\", \"efficiency\": \"80+ Gold\", \"modular\": \"Full\", \"fan_size\": \"120mm\", \"pfc\": \"Active\"}', 8500, 0, 750, '80+ Gold', '{\"wattage\":750,\"efficiency\":\"80+ Gold\"}', 'in_stock', 32, 4.68, 'mainstream', '160×150×86', 1.55, '28 dBA', '80+ Gold', '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(167, 6, 'DeepCool PF650', 'DeepCool', 'R-PF650D-HA0B-EU', 6990.00, 2022, 60, NULL, '{\"wattage\": \"650W\", \"efficiency\": \"80+ Bronze\", \"modular\": \"Semi-modular\", \"fan_size\": \"120mm\", \"pfc\": \"Active\"}', 8000, 0, 650, '80+ Bronze', '{\"wattage\":650,\"efficiency\":\"80+ Bronze\"}', 'in_stock', 40, 4.52, 'budget', '140×150×86', 1.40, '30 dBA', '80+ Bronze', '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(168, 6, 'Corsair HX1200 1200W 80+ Platinum', 'Corsair', 'HX1200', 24990.00, 2017, 120, NULL, '{\"wattage\":1200,\"efficiency\":\"80+ Platinum\",\"modular\":\"Fully Modular\",\"fan_size\":\"140mm\"}', 9600, 0, 1200, '80+ Platinum', '{\"wattage\":1200,\"efficiency\":\"80+ Platinum\"}', 'in_stock', 10, 4.90, 'flagship', '200×150×86', 2.40, '25 dBA', '80+ Platinum', '2025-11-27 19:34:15', '2025-11-27 19:34:15'),
(169, 6, 'be quiet! Pure Power 12 M 850W', 'be quiet!', 'BN343', 14990.00, 2023, 120, NULL, '{\"wattage\":850,\"efficiency\":\"80+ Gold\",\"modular\":\"Fully Modular\",\"fan_size\":\"120mm\",\"pcie5_12vhpwr\":1}', 9000, 0, 850, '80+ Gold', '{\"wattage\":850,\"efficiency\":\"80+ Gold\"}', 'in_stock', 25, 4.80, 'enthusiast', '160×150×86', 1.80, '26 dBA', '80+ Gold', '2025-11-27 19:34:15', '2025-11-27 19:34:15'),
(170, 6, 'Seasonic PRIME TX-1000', 'Seasonic', 'PRIME-TX-1000', 28990.00, 2020, 144, NULL, '{\"wattage\":1000,\"efficiency\":\"80+ Titanium\",\"modular\":\"Fully Modular\",\"fan_size\":\"135mm\"}', 9800, 0, 1000, '80+ Titanium', '{\"wattage\":1000,\"efficiency\":\"80+ Titanium\"}', 'in_stock', 8, 4.95, 'flagship', '170×150×86', 2.10, '23 dBA', '80+ Titanium', '2025-11-27 19:34:15', '2025-11-27 19:34:15');

-- --------------------------------------------------------

--
-- Структура таблицы `components_ram`
--

CREATE TABLE `components_ram` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(150) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `release_year` smallint(6) DEFAULT NULL,
  `warranty_months` smallint(6) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `performance_score` int(11) DEFAULT NULL,
  `power_consumption` int(11) DEFAULT NULL,
  `ram_capacity` int(11) DEFAULT NULL,
  `ram_type` varchar(10) DEFAULT NULL,
  `ram_speed` int(11) DEFAULT NULL,
  `compatibility_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compatibility_info`)),
  `stock_status` enum('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
  `stock_quantity` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `tier` enum('flagship','enthusiast','mainstream','budget') DEFAULT 'mainstream',
  `dimensions_mm` varchar(50) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `noise_level` varchar(20) DEFAULT NULL,
  `efficiency_class` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `components_ram`
--

INSERT INTO `components_ram` (`id`, `category_id`, `name`, `manufacturer`, `model`, `price`, `release_year`, `warranty_months`, `image`, `specs`, `performance_score`, `power_consumption`, `ram_capacity`, `ram_type`, `ram_speed`, `compatibility_info`, `stock_status`, `stock_quantity`, `rating`, `tier`, `dimensions_mm`, `weight_kg`, `noise_level`, `efficiency_class`, `created_at`, `updated_at`) VALUES
(16, 4, 'Corsair Dominator Platinum RGB 32GB DDR5-6000', 'Corsair', 'DDR5-6000', 16990.00, 2023, 60, NULL, '{\"capacity\": \"32GB\", \"type\": \"DDR5\", \"speed\": \"6000MHz\", \"cas_latency\": \"CL30\", \"modules\": \"2x16GB\"}', 8500, 10, 32, 'DDR5', 6000, '{\"type\": \"DDR5\", \"speed\": 6000, \"voltage\": \"1.35V\"}', 'in_stock', 32, 4.78, 'enthusiast', '133×40×8', 0.19, NULL, NULL, '2025-11-22 17:53:07', '2025-11-23 09:03:49'),
(17, 4, 'G.Skill Trident Z5 RGB 32GB DDR5-6400', 'G.Skill', 'DDR5-6400', 18990.00, 2023, 60, NULL, '{\"capacity\": \"32GB\", \"type\": \"DDR5\", \"speed\": \"6400MHz\", \"cas_latency\": \"CL32\", \"modules\": \"2x16GB\"}', 8500, 12, 32, 'DDR5', 6400, '{\"type\": \"DDR5\", \"speed\": 6400, \"voltage\": \"1.40V\"}', 'in_stock', 26, 4.81, 'enthusiast', '133×42×8', 0.20, NULL, NULL, '2025-11-22 17:53:07', '2025-11-23 09:11:50'),
(18, 4, 'Kingston Fury Beast 32GB DDR5-5600', 'Kingston', 'DDR5-5600', 13990.00, 2023, 60, NULL, '{\"capacity\": \"32GB\", \"type\": \"DDR5\", \"speed\": \"5600MHz\", \"cas_latency\": \"CL36\", \"modules\": \"2x16GB\"}', 8000, 10, 32, 'DDR5', 5600, '{\"type\": \"DDR5\", \"speed\": 5600, \"voltage\": \"1.25V\"}', 'in_stock', 30, 4.55, 'mainstream', '133×34×8', 0.18, NULL, NULL, '2025-11-22 17:53:07', '2025-11-23 09:11:50'),
(37, 4, 'TEAMGROUP T-Force Delta RGB 32GB DDR5-7200', 'TEAMGROUP', 'Delta RGB 7200', 20990.00, 2023, 60, NULL, '{\"capacity\":\"32GB\",\"type\":\"DDR5\",\"speed\":\"7200MHz\",\"cas_latency\":\"CL34\",\"modules\":\"2x16GB\"}', 9000, 8, 32, 'DDR5', 7200, '{\"type\":\"DDR5\",\"speed\":7200,\"voltage\":\"1.45V\"}', 'in_stock', 30, 4.79, 'enthusiast', NULL, 0.20, NULL, NULL, '2025-11-22 20:38:24', '2025-11-23 09:11:50'),
(38, 4, 'Patriot Viper Venom 64GB DDR5-6000', 'Patriot', 'Viper Venom 6000', 26990.00, 2023, 60, NULL, '{\"capacity\":\"64GB\",\"type\":\"DDR5\",\"speed\":\"6000MHz\",\"cas_latency\":\"CL36\",\"modules\":\"2x32GB\"}', 8500, 10, 64, 'DDR5', 6000, '{\"type\":\"DDR5\",\"speed\":6000,\"voltage\":\"1.35V\"}', 'in_stock', 14, 4.66, 'enthusiast', NULL, 0.24, NULL, NULL, '2025-11-22 20:38:24', '2025-11-23 09:11:50'),
(64, 4, 'Corsair Vengeance DDR5 32GB', 'Corsair', 'CMK32GX5M2B5600C36', 11990.00, 2022, 120, NULL, '{\"capacity\": \"32GB\", \"type\": \"DDR5\", \"speed\": \"5600 MHz\", \"latency\": \"CL36\", \"voltage\": \"1.25V\", \"modules\": \"2x16GB\"}', 8000, 8, 32, 'DDR5', 5600, '{\"type\":\"DDR5\",\"speed\":5600,\"voltage\":\"1.25V\"}', 'in_stock', 50, 4.75, 'mainstream', '133×51×8', 0.14, NULL, NULL, '2025-11-22 22:14:44', '2025-11-23 09:11:50'),
(65, 4, 'Kingston FURY Beast 16GB', 'Kingston', 'KF432C16BBK2/16', 4990.00, 2021, 120, NULL, '{\"capacity\": \"16GB\", \"type\": \"DDR4\", \"speed\": \"3200 MHz\", \"latency\": \"CL16\", \"voltage\": \"1.35V\", \"modules\": \"2x8GB\"}', 7000, 8, 16, 'DDR4', 3200, '{\"type\":\"DDR4\",\"speed\":3200,\"voltage\":\"1.35V\"}', 'in_stock', 65, 4.60, 'budget', '133×42×7', 0.08, NULL, NULL, '2025-11-22 22:14:44', '2025-11-23 09:11:50'),
(66, 4, 'Corsair Dominator Platinum RGB 64GB', 'Corsair', 'CMT64GX5M2B6400C32', 29990.00, 2023, 120, NULL, '{\"capacity\": \"64GB\", \"type\": \"DDR5\", \"speed\": \"6400 MHz\", \"latency\": \"CL32\", \"voltage\": \"1.40V\", \"modules\": \"2x32GB\"}', 8500, 10, 64, 'DDR5', 6400, '{\"type\":\"DDR5\",\"speed\":6400,\"voltage\":\"1.40V\"}', 'in_stock', 18, 4.95, 'flagship', '133×56×8', 0.18, NULL, NULL, '2025-11-22 22:14:44', '2025-11-23 09:11:50'),
(153, 4, 'Kingston FURY Beast RGB 32GB DDR5-6000', 'Kingston', 'KF560C36BBEAK2-32', 13990.00, 2023, 120, NULL, '{\"capacity\": \"32GB\", \"type\": \"DDR5\", \"speed\": \"6000 MHz\", \"latency\": \"CL36\", \"voltage\": \"1.35V\", \"modules\": \"2x16GB\"}', 8500, 8, 32, 'DDR5', 6000, '{\"type\":\"DDR5\",\"speed\":6000,\"voltage\":\"1.35V\"}', 'in_stock', 38, 4.70, 'enthusiast', '133×42×8', 0.16, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(154, 4, 'Crucial Pro 32GB DDR5-5600', 'Crucial', 'CP2K16G56C46U5', 10990.00, 2023, 120, NULL, '{\"capacity\": \"32GB\", \"type\": \"DDR5\", \"speed\": \"5600 MHz\", \"latency\": \"CL46\", \"voltage\": \"1.25V\", \"modules\": \"2x16GB\"}', 8000, 8, 32, 'DDR5', 5600, '{\"type\":\"DDR5\",\"speed\":5600,\"voltage\":\"1.25V\"}', 'in_stock', 45, 4.62, 'mainstream', '133×34×8', 0.15, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(155, 4, 'G.Skill Ripjaws S5 64GB DDR5-6000', 'G.Skill', 'F5-6000J3636F32GX2-RS5K', 24990.00, 2023, 120, NULL, '{\"capacity\": \"64GB\", \"type\": \"DDR5\", \"speed\": \"6000 MHz\", \"latency\": \"CL36\", \"voltage\": \"1.35V\", \"modules\": \"2x32GB\"}', 8500, 10, 64, 'DDR5', 6000, '{\"type\":\"DDR5\",\"speed\":6000,\"voltage\":\"1.35V\"}', 'in_stock', 22, 4.78, 'enthusiast', '133×42×8', 0.22, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(156, 4, 'Corsair Vengeance RGB 32GB DDR4-3600', 'Corsair', 'CMH32GX4M2D3600C18', 8990.00, 2021, 120, NULL, '{\"capacity\": \"32GB\", \"type\": \"DDR4\", \"speed\": \"3600 MHz\", \"latency\": \"CL18\", \"voltage\": \"1.35V\", \"modules\": \"2x16GB\"}', 7000, 8, 32, 'DDR4', 3600, '{\"type\":\"DDR4\",\"speed\":3600,\"voltage\":\"1.35V\"}', 'in_stock', 42, 4.65, 'mainstream', '133×51×8', 0.14, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(157, 4, 'TEAMGROUP T-Force Vulcan Z 16GB DDR4-3200', 'TEAMGROUP', 'TLZGD416G3200HC16CDC01', 3990.00, 2020, 120, NULL, '{\"capacity\": \"16GB\", \"type\": \"DDR4\", \"speed\": \"3200 MHz\", \"latency\": \"CL16\", \"voltage\": \"1.35V\", \"modules\": \"2x8GB\"}', 7000, 8, 16, 'DDR4', 3200, '{\"type\":\"DDR4\",\"speed\":3200,\"voltage\":\"1.35V\"}', 'in_stock', 60, 4.48, 'budget', '133×34×7', 0.07, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(158, 4, 'Corsair Vengeance RGB 32GB DDR5-6000', 'Corsair', 'CMH32GX5M2D6000C36', 13990.00, 2023, 120, NULL, '{\"capacity\":\"32GB\",\"type\":\"DDR5\",\"speed\":\"6000 MHz\",\"latency\":\"CL36\",\"voltage\":\"1.35V\",\"modules\":\"2x16GB\"}', 8300, 8, 32, 'DDR5', 6000, '{\"type\":\"DDR5\",\"speed\":6000,\"voltage\":\"1.35V\"}', 'in_stock', 40, 4.75, 'enthusiast', '133×51×8', 0.16, NULL, NULL, '2025-11-27 19:34:05', '2025-11-27 19:34:05'),
(159, 4, 'G.Skill Trident Z5 Neo RGB 32GB DDR5-6000', 'G.Skill', 'F5-6000J3038F16GX2-TZ5N', 17990.00, 2023, 120, NULL, '{\"capacity\":\"32GB\",\"type\":\"DDR5\",\"speed\":\"6000 MHz\",\"latency\":\"CL30\",\"voltage\":\"1.35V\",\"modules\":\"2x16GB\"}', 8600, 9, 32, 'DDR5', 6000, '{\"type\":\"DDR5\",\"speed\":6000,\"voltage\":\"1.35V\"}', 'in_stock', 25, 4.85, 'enthusiast', '133×42×8', 0.20, NULL, NULL, '2025-11-27 19:34:05', '2025-11-27 19:34:05'),
(160, 4, 'Kingston Fury Beast 32GB DDR4-3200', 'Kingston', 'KF432C16BBK2/32', 6990.00, 2021, 120, NULL, '{\"capacity\":\"32GB\",\"type\":\"DDR4\",\"speed\":\"3200 MHz\",\"latency\":\"CL16\",\"voltage\":\"1.35V\",\"modules\":\"2x16GB\"}', 7200, 8, 32, 'DDR4', 3200, '{\"type\":\"DDR4\",\"speed\":3200,\"voltage\":\"1.35V\"}', 'in_stock', 50, 4.65, 'mainstream', '133×42×7', 0.09, NULL, NULL, '2025-11-27 19:34:05', '2025-11-27 19:34:05'),
(161, 4, 'Crucial Pro 64GB DDR5-5600', 'Crucial', 'CP2K32G56C46U5', 19990.00, 2023, 120, NULL, '{\"capacity\":\"64GB\",\"type\":\"DDR5\",\"speed\":\"5600 MHz\",\"latency\":\"CL46\",\"voltage\":\"1.25V\",\"modules\":\"2x32GB\"}', 8200, 10, 64, 'DDR5', 5600, '{\"type\":\"DDR5\",\"speed\":5600,\"voltage\":\"1.25V\"}', 'in_stock', 30, 4.70, 'enthusiast', '133×34×8', 0.24, NULL, NULL, '2025-11-27 19:34:05', '2025-11-27 19:34:05'),
(162, 4, 'G.Skill Aegis 16GB DDR4-3200', 'G.Skill', 'F4-3200C16D-16GIS', 3490.00, 2020, 120, NULL, '{\"capacity\":\"16GB\",\"type\":\"DDR4\",\"speed\":\"3200 MHz\",\"latency\":\"CL16\",\"voltage\":\"1.35V\",\"modules\":\"2x8GB\"}', 6800, 6, 16, 'DDR4', 3200, '{\"type\":\"DDR4\",\"speed\":3200,\"voltage\":\"1.35V\"}', 'in_stock', 70, 4.50, 'budget', '133×31×7', 0.07, NULL, NULL, '2025-11-27 19:34:05', '2025-11-27 19:34:05');

-- --------------------------------------------------------

--
-- Структура таблицы `components_storage`
--

CREATE TABLE `components_storage` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(150) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `release_year` smallint(6) DEFAULT NULL,
  `warranty_months` smallint(6) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `performance_score` int(11) DEFAULT NULL,
  `power_consumption` int(11) DEFAULT NULL,
  `storage_capacity` int(11) DEFAULT NULL,
  `storage_type` varchar(20) DEFAULT NULL,
  `storage_interface` varchar(20) DEFAULT NULL,
  `compatibility_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compatibility_info`)),
  `stock_status` enum('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
  `stock_quantity` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `tier` enum('flagship','enthusiast','mainstream','budget') DEFAULT 'mainstream',
  `dimensions_mm` varchar(50) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `noise_level` varchar(20) DEFAULT NULL,
  `efficiency_class` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `components_storage`
--

INSERT INTO `components_storage` (`id`, `category_id`, `name`, `manufacturer`, `model`, `price`, `release_year`, `warranty_months`, `image`, `specs`, `performance_score`, `power_consumption`, `storage_capacity`, `storage_type`, `storage_interface`, `compatibility_info`, `stock_status`, `stock_quantity`, `rating`, `tier`, `dimensions_mm`, `weight_kg`, `noise_level`, `efficiency_class`, `created_at`, `updated_at`) VALUES
(19, 5, 'Samsung 990 PRO 2TB NVMe SSD', 'Samsung', '990 PRO', 17990.00, 2022, 60, NULL, '{\"capacity\": \"2TB\", \"interface\": \"PCIe 4.0 x4\", \"form_factor\": \"M.2 2280\", \"read_speed\": \"7450 MB/s\", \"write_speed\": \"6900 MB/s\"}', 9500, 7, 2000, 'NVMe', 'PCIe 4.0 x4', '{\"interface\": \"NVMe\", \"form_factor\": \"M.2\"}', 'in_stock', 27, 4.84, 'flagship', '80×22×3.5', 0.02, NULL, NULL, '2025-11-22 17:53:07', '2025-11-23 09:11:50'),
(20, 5, 'WD Black SN850X 1TB NVMe SSD', 'Western Digital', 'SN850X', 9990.00, 2021, 60, NULL, '{\"capacity\": \"1TB\", \"interface\": \"PCIe 4.0 x4\", \"form_factor\": \"M.2 2280\", \"read_speed\": \"7300 MB/s\", \"write_speed\": \"6300 MB/s\"}', 9000, 6, 1000, 'NVMe', 'PCIe 4.0 x4', '{\"interface\": \"NVMe\", \"form_factor\": \"M.2\"}', 'in_stock', 34, 4.71, 'enthusiast', '80×22×3.5', 0.02, NULL, NULL, '2025-11-22 17:53:07', '2025-11-23 09:11:50'),
(21, 5, 'Crucial P5 Plus 2TB NVMe SSD', 'Crucial', 'P5 Plus', 14990.00, 2021, 60, NULL, '{\"capacity\": \"2TB\", \"interface\": \"PCIe 4.0 x4\", \"form_factor\": \"M.2 2280\", \"read_speed\": \"6600 MB/s\", \"write_speed\": \"5000 MB/s\"}', 9500, 6, 2000, 'NVMe', 'PCIe 4.0 x4', '{\"interface\": \"NVMe\", \"form_factor\": \"M.2\"}', 'in_stock', 22, 4.58, 'mainstream', '80×22×3.5', 0.02, NULL, NULL, '2025-11-22 17:53:07', '2025-11-23 09:11:50'),
(39, 5, 'Seagate FireCuda 530 2TB', 'Seagate', 'FireCuda 530', 19990.00, 2022, 60, NULL, '{\"capacity\":\"2TB\",\"interface\":\"PCIe 4.0 x4\",\"form_factor\":\"M.2 2280\",\"read_speed\":\"7300 MB/s\",\"write_speed\":\"6900 MB/s\"}', 9500, 7, 2000, 'NVMe', 'PCIe 4.0 x4', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 20, 4.82, 'enthusiast', '80×22×3.5', 0.02, NULL, NULL, '2025-11-22 20:38:24', '2025-11-23 09:11:50'),
(40, 5, 'ADATA Legend 960 MAX 1TB', 'ADATA', 'Legend 960 MAX', 12990.00, 2023, 60, NULL, '{\"capacity\":\"1TB\",\"interface\":\"PCIe 4.0 x4\",\"form_factor\":\"M.2 2280\",\"read_speed\":\"7400 MB/s\",\"write_speed\":\"6000 MB/s\"}', 9000, 6, 1000, 'NVMe', 'PCIe 4.0 x4', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 24, 4.57, 'mainstream', '80×22×3.5', 0.02, NULL, NULL, '2025-11-22 20:38:24', '2025-11-23 09:11:50'),
(118, 5, 'WD Black SN850X 1TB', 'Western Digital', 'WDS100T2X0E', 9990.00, 2022, 60, NULL, '{\"capacity\": \"1TB\", \"type\": \"NVMe\", \"interface\": \"PCIe 4.0\", \"form_factor\": \"M.2 2280\", \"read_speed\": \"7300 MB/s\", \"write_speed\": \"6300 MB/s\"}', 9000, 5, 1000, 'NVMe', 'PCIe 4.0', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 45, 4.85, 'enthusiast', '80×22×2.38', 0.01, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(119, 5, 'Crucial P3 Plus 1TB', 'Crucial', 'CT1000P3PSSD8', 6990.00, 2022, 60, NULL, '{\"capacity\": \"1TB\", \"type\": \"NVMe\", \"interface\": \"PCIe 4.0\", \"form_factor\": \"M.2 2280\", \"read_speed\": \"5000 MB/s\", \"write_speed\": \"4200 MB/s\"}', 9000, 5, 1000, 'NVMe', 'PCIe 4.0', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 55, 4.65, 'mainstream', '80×22×2.2', 0.01, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(120, 5, 'Kingston NV2 500GB', 'Kingston', 'SNV2S/500G', 3490.00, 2022, 36, NULL, '{\"capacity\": \"500GB\", \"type\": \"NVMe\", \"interface\": \"PCIe 4.0\", \"form_factor\": \"M.2 2280\", \"read_speed\": \"3500 MB/s\", \"write_speed\": \"2100 MB/s\"}', 9000, 5, 500, 'NVMe', 'PCIe 4.0', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 70, 4.40, 'budget', '80×22×2.2', 0.01, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(121, 5, 'Seagate BarraCuda 2TB HDD', 'Seagate', 'ST2000DM008', 4990.00, 2021, 24, NULL, '{\"capacity\": \"2TB\", \"type\": \"HDD\", \"interface\": \"SATA\", \"form_factor\": \"3.5 inch\", \"rpm\": \"7200\", \"cache\": \"256MB\"}', 6000, 7, 2000, 'HDD', 'SATA', '{\"interface\":\"SATA\",\"form_factor\":\"3.5 inch\"}', 'in_stock', 40, 4.50, 'budget', '147×101.6×20.2', 0.42, NULL, NULL, '2025-11-22 22:17:07', '2025-11-23 09:11:50'),
(158, 5, 'Samsung 980 PRO 1TB', 'Samsung', 'MZ-V8P1T0BW', 11990.00, 2020, 60, NULL, '{\"capacity\": \"1TB\", \"type\": \"NVMe\", \"interface\": \"PCIe 4.0\", \"form_factor\": \"M.2 2280\", \"read_speed\": \"7000 MB/s\", \"write_speed\": \"5000 MB/s\"}', 9000, 6, 1000, 'NVMe', 'PCIe 4.0', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 40, 4.80, 'enthusiast', '80×22×2.3', 0.01, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(159, 5, 'Kingston KC3000 2TB', 'Kingston', 'SKC3000D/2048G', 18990.00, 2021, 60, NULL, '{\"capacity\": \"2TB\", \"type\": \"NVMe\", \"interface\": \"PCIe 4.0\", \"form_factor\": \"M.2 2280\", \"read_speed\": \"7000 MB/s\", \"write_speed\": \"7000 MB/s\"}', 9500, 7, 2000, 'NVMe', 'PCIe 4.0', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 28, 4.75, 'enthusiast', '80×22×2.2', 0.01, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(160, 5, 'WD Blue SN580 1TB', 'Western Digital', 'WDS100T3B0E', 7990.00, 2023, 60, NULL, '{\"capacity\": \"1TB\", \"type\": \"NVMe\", \"interface\": \"PCIe 4.0\", \"form_factor\": \"M.2 2280\", \"read_speed\": \"4150 MB/s\", \"write_speed\": \"4150 MB/s\"}', 9000, 5, 1000, 'NVMe', 'PCIe 4.0', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 50, 4.58, 'mainstream', '80×22×2.38', 0.01, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(161, 5, 'Crucial P3 500GB', 'Crucial', 'CT500P3SSD8', 4490.00, 2022, 60, NULL, '{\"capacity\": \"500GB\", \"type\": \"NVMe\", \"interface\": \"PCIe 3.0\", \"form_factor\": \"M.2 2280\", \"read_speed\": \"3500 MB/s\", \"write_speed\": \"3000 MB/s\"}', 8000, 4, 500, 'NVMe', 'PCIe 3.0', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 65, 4.50, 'budget', '80×22×2.2', 0.01, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(162, 5, 'WD Black 4TB HDD', 'Western Digital', 'WD4005FZBX', 8990.00, 2020, 60, NULL, '{\"capacity\": \"4TB\", \"type\": \"HDD\", \"interface\": \"SATA\", \"form_factor\": \"3.5 inch\", \"rpm\": \"7200\", \"cache\": \"256MB\"}', 6000, 8, 4000, 'HDD', 'SATA', '{\"interface\":\"SATA\",\"form_factor\":\"3.5 inch\"}', 'in_stock', 35, 4.72, 'mainstream', '147×101.6×26.1', 0.72, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(163, 5, 'Toshiba P300 3TB HDD', 'Toshiba', 'HDWD130UZSVA', 5990.00, 2021, 24, NULL, '{\"capacity\": \"3TB\", \"type\": \"HDD\", \"interface\": \"SATA\", \"form_factor\": \"3.5 inch\", \"rpm\": \"7200\", \"cache\": \"64MB\"}', 6000, 7, 3000, 'HDD', 'SATA', '{\"interface\":\"SATA\",\"form_factor\":\"3.5 inch\"}', 'in_stock', 45, 4.55, 'budget', '147×101.6×26.1', 0.45, NULL, NULL, '2025-11-23 07:09:55', '2025-11-23 09:11:50'),
(164, 5, 'Samsung 970 EVO Plus 1TB', 'Samsung', 'MZ-V7S1T0BW', 8990.00, 2019, 60, NULL, '{\"capacity\":\"1TB\",\"type\":\"NVMe\",\"interface\":\"PCIe 3.0 x4\",\"form_factor\":\"M.2 2280\",\"read_speed\":\"3500 MB/s\",\"write_speed\":\"3300 MB/s\"}', 8500, 6, 1000, 'NVMe', 'PCIe 3.0', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 40, 4.80, 'mainstream', '80×22×2.3', 0.01, NULL, NULL, '2025-11-27 19:34:10', '2025-11-27 19:34:10'),
(165, 5, 'WD Blue SN570 1TB', 'Western Digital', 'WDS100T3B0C', 6990.00, 2021, 60, NULL, '{\"capacity\":\"1TB\",\"type\":\"NVMe\",\"interface\":\"PCIe 3.0 x4\",\"form_factor\":\"M.2 2280\",\"read_speed\":\"3500 MB/s\",\"write_speed\":\"3000 MB/s\"}', 8200, 5, 1000, 'NVMe', 'PCIe 3.0', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 60, 4.60, 'budget', '80×22×2.2', 0.01, NULL, NULL, '2025-11-27 19:34:10', '2025-11-27 19:34:10'),
(166, 5, 'Crucial MX500 1TB', 'Crucial', 'CT1000MX500SSD1', 6490.00, 2018, 60, NULL, '{\"capacity\":\"1TB\",\"type\":\"SSD\",\"interface\":\"SATA\",\"form_factor\":\"2.5 inch\",\"read_speed\":\"560 MB/s\",\"write_speed\":\"510 MB/s\"}', 7800, 4, 1000, 'SSD', 'SATA', '{\"interface\":\"SATA\",\"form_factor\":\"2.5 inch\"}', 'in_stock', 50, 4.70, 'mainstream', '100×70×7', 0.05, NULL, NULL, '2025-11-27 19:34:10', '2025-11-27 19:34:10'),
(167, 5, 'Seagate FireCuda 510 1TB', 'Seagate', 'ZP1000GM30001', 8990.00, 2019, 60, NULL, '{\"capacity\":\"1TB\",\"type\":\"NVMe\",\"interface\":\"PCIe 3.0 x4\",\"form_factor\":\"M.2 2280\",\"read_speed\":\"3450 MB/s\",\"write_speed\":\"3200 MB/s\"}', 8400, 6, 1000, 'NVMe', 'PCIe 3.0', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 35, 4.65, 'mainstream', '80×22×2.3', 0.01, NULL, NULL, '2025-11-27 19:34:10', '2025-11-27 19:34:10'),
(168, 5, 'Kingston A2000 1TB', 'Kingston', 'SA2000M8/1000G', 6490.00, 2019, 60, NULL, '{\"capacity\":\"1TB\",\"type\":\"NVMe\",\"interface\":\"PCIe 3.0 x4\",\"form_factor\":\"M.2 2280\",\"read_speed\":\"2200 MB/s\",\"write_speed\":\"2000 MB/s\"}', 8000, 5, 1000, 'NVMe', 'PCIe 3.0', '{\"interface\":\"NVMe\",\"form_factor\":\"M.2\"}', 'in_stock', 45, 4.55, 'budget', '80×22×2.2', 0.01, NULL, NULL, '2025-11-27 19:34:10', '2025-11-27 19:34:10');

-- --------------------------------------------------------

--
-- Структура таблицы `component_reviews`
--

CREATE TABLE `component_reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `component_category_id` int(11) NOT NULL,
  `component_name` varchar(255) NOT NULL,
  `component_slug` varchar(150) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT 0,
  `title` varchar(160) NOT NULL,
  `summary` text NOT NULL,
  `pros` text DEFAULT NULL,
  `cons` text DEFAULT NULL,
  `usage_context` varchar(255) DEFAULT NULL,
  `recommended` tinyint(1) NOT NULL DEFAULT 1,
  `helpful_votes` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','published','archived') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `component_reviews`
--

INSERT INTO `component_reviews` (`id`, `user_id`, `component_id`, `component_category_id`, `component_name`, `component_slug`, `rating`, `title`, `summary`, `pros`, `cons`, `usage_context`, `recommended`, `helpful_votes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 164, 6, 'EVGA SuperNOVA 1000 G6', 'evga-supernova-1000-g6', 4, 'Крутой БП до 15к', 'Ну БП крутой, неплохой в принципе, сам по себе подходит под игровые сборки, стриминг и подобное', 'Очень мощный\r\nПодходит под игры и стриминг\r\nКачественный', 'Дорогой', 'Монтаж, стриминг, игры, 3-д рендертнг', 1, 0, 'published', '2025-11-28 20:00:32', '2025-11-28 20:00:54');

-- --------------------------------------------------------

--
-- Структура таблицы `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `release_year` int(11) DEFAULT NULL,
  `min_requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`min_requirements`)),
  `recommended_requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recommended_requirements`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `games`
--

INSERT INTO `games` (`id`, `name`, `image`, `release_year`, `min_requirements`, `recommended_requirements`, `created_at`) VALUES
(1, 'Cyberpunk 2077', NULL, 2020, '{\"cpu\": \"Intel Core i5-3570K\", \"gpu\": \"GTX 780\", \"ram\": \"8 GB\"}', '{\"cpu\": \"Intel Core i7-4790\", \"gpu\": \"GTX 1060 6GB\", \"ram\": \"12 GB\"}', '2025-11-22 17:53:07'),
(2, 'Red Dead Redemption 2', NULL, 2019, '{\"cpu\": \"Intel Core i5-2500K\", \"gpu\": \"GTX 770\", \"ram\": \"8 GB\"}', '{\"cpu\": \"Intel Core i7-4770K\", \"gpu\": \"GTX 1060 6GB\", \"ram\": \"12 GB\"}', '2025-11-22 17:53:07'),
(3, 'Elden Ring', NULL, 2022, '{\"cpu\": \"Intel Core i5-8400\", \"gpu\": \"GTX 1060\", \"ram\": \"12 GB\"}', '{\"cpu\": \"Intel Core i7-8700K\", \"gpu\": \"GTX 1070\", \"ram\": \"16 GB\"}', '2025-11-22 17:53:07'),
(4, 'CS2 (Counter-Strike 2)', NULL, 2023, '{\"cpu\": \"Intel Core i5-9600K\", \"gpu\": \"GTX 1060\", \"ram\": \"8 GB\"}', '{\"cpu\": \"Intel Core i7-9700K\", \"gpu\": \"RTX 2070\", \"ram\": \"16 GB\"}', '2025-11-22 17:53:07'),
(5, 'Starfield', NULL, 2023, '{\"cpu\": \"AMD Ryzen 5 2600X\", \"gpu\": \"RX 5700\", \"ram\": \"16 GB\"}', '{\"cpu\": \"AMD Ryzen 5 3600X\", \"gpu\": \"RX 6800 XT\", \"ram\": \"16 GB\"}', '2025-11-22 17:53:07'),
(6, 'Baldur\'s Gate 3', NULL, 2023, '{\"cpu\": \"Intel Core i5-4690\", \"gpu\": \"GTX 970\", \"ram\": \"8 GB\"}', '{\"cpu\": \"Intel Core i7-8700K\", \"gpu\": \"RTX 2060\", \"ram\": \"16 GB\"}', '2025-11-22 17:53:07');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('pending','confirmed','processing','assembling','shipping','shipped','ready_pickup','delivered','completed','cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Order status: pending, confirmed, processing, assembling, shipping, shipped, ready_pickup, delivered, completed, cancelled',
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_method` varchar(100) DEFAULT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `status`, `total_amount`, `delivery_address`, `delivery_method`, `payment_method`, `payment_status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'ORD-22CA1750', 'shipping', 410920.00, 'Юбилейная 24, ывффыв, 160010', 'courier', 'card', 'paid', '', '2025-11-24 19:53:10', '2025-11-25 20:54:37'),
(3, 1, 'ORD-3F32C5F4', 'cancelled', 190810.00, 'Юбилейная 24, Вологда, 160010', 'pickup', 'cash', 'pending', '', '2025-11-25 21:17:45', '2025-11-27 08:24:30');

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  `component_name` varchar(255) NOT NULL,
  `component_category` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `component_id`, `component_name`, `component_category`, `quantity`, `price`, `created_at`) VALUES
(1, 1, 1, 'Intel Core i9-14900K', 'Unknown', 1, 52990.00, '2025-11-24 19:53:10'),
(2, 1, 179, 'NVIDIA GeForce RTX 5090', 'Unknown', 1, 249990.00, '2025-11-24 19:53:10'),
(3, 1, 152, 'MSI PRO B760M-A WIFI', 'Unknown', 1, 16990.00, '2025-11-24 19:53:10'),
(4, 1, 66, 'Corsair Dominator Platinum RGB 64GB', 'Unknown', 1, 29990.00, '2025-11-24 19:53:10'),
(5, 1, 21, 'Crucial P5 Plus 2TB NVMe SSD', 'Unknown', 1, 14990.00, '2025-11-24 19:53:10'),
(6, 1, 122, 'Corsair RM1000x', 'Unknown', 1, 16990.00, '2025-11-24 19:53:10'),
(7, 1, 168, 'Corsair 5000D AIRFLOW', 'Unknown', 1, 15990.00, '2025-11-24 19:53:10'),
(8, 1, 29, 'Arctic Liquid Freezer II 360', 'Unknown', 1, 12990.00, '2025-11-24 19:53:10'),
(17, 3, 32, 'AMD Ryzen 5 8600G', 'Unknown', 1, 23990.00, '2025-11-25 21:17:45'),
(18, 3, 182, 'NVIDIA GeForce RTX 5070', 'Unknown', 1, 79990.00, '2025-11-25 21:17:45'),
(19, 3, 151, 'ASRock B650M Pro RS', 'Unknown', 1, 14990.00, '2025-11-25 21:17:45'),
(20, 3, 153, 'Kingston FURY Beast RGB 32GB DDR5-6000', 'Unknown', 1, 13990.00, '2025-11-25 21:17:45'),
(21, 3, 19, 'Samsung 990 PRO 2TB NVMe SSD', 'Unknown', 1, 17990.00, '2025-11-25 21:17:45'),
(22, 3, 120, 'Kingston NV2 500GB', 'Unknown', 1, 3490.00, '2025-11-25 21:17:45'),
(23, 3, 41, 'MSI MPG A850G PCIE5', 'Unknown', 1, 16390.00, '2025-11-25 21:17:45'),
(24, 3, 128, 'NZXT H510 Elite', 'Unknown', 1, 9990.00, '2025-11-25 21:17:45'),
(25, 3, 46, 'DeepCool AK620 Zero Dark', 'Unknown', 1, 9990.00, '2025-11-25 21:17:45');

-- --------------------------------------------------------

--
-- Структура таблицы `order_notifications`
--

CREATE TABLE `order_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL COMMENT 'Order ID for type=status, Ticket ID for type=support',
  `type` enum('status','support','system') NOT NULL DEFAULT 'system',
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `order_notifications`
--

INSERT INTO `order_notifications` (`id`, `user_id`, `order_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 1, 'support', 'Новое сообщение по заказу #ORD-22CA1750', 'rasd', 1, '2025-11-25 16:52:54'),
(2, 1, 1, 'status', 'Статус заказа #ORD-22CA1750 обновлён', 'Новый статус: Передан Курьеру', 1, '2025-11-25 16:53:09'),
(3, 1, 3, 'status', 'Заказ #ORD-3F32C5F4 отменён', 'Вы отменили заказ. Мы надеемся увидеть вас снова!', 1, '2025-11-27 08:24:30');

-- --------------------------------------------------------

--
-- Структура таблицы `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `code_hash`, `expires_at`, `created_at`) VALUES
(2, 1, '0f31eeb23a75c8bd33ad3f6d1dc5cf9299a2d71044ee8602aebb178db25add79', '$2y$10$VKvv7ZqrpRpezoygOZInOOfpLYj2rjIEIpdJAsBXsssYl7KK1Jgn6', '2025-11-30 21:13:30', '2025-11-30 23:58:30');

-- --------------------------------------------------------

--
-- Структура таблицы `support_messages`
--

CREATE TABLE `support_messages` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_support` tinyint(1) DEFAULT 0,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `support_messages`
--

INSERT INTO `support_messages` (`id`, `order_id`, `user_id`, `message`, `is_support`, `is_read`, `created_at`) VALUES
(1, 1, 1, 'Здравствуйте! Когда будет отправлен мой заказ?', 0, 1, '2025-11-24 18:13:32'),
(2, 1, NULL, 'Добрый день! Ваш заказ находится в обработке. Отправка планируется завтра. Вы получите трек-номер на указанный email.', 1, 1, '2025-11-24 19:43:32'),
(3, 1, 1, 'Спасибо за информацию! А можно изменить адрес доставки?', 0, 1, '2025-11-24 19:13:32'),
(4, 1, NULL, 'Да, конечно! Пожалуйста, укажите новый адрес доставки, и мы внесем изменения в ваш заказ.', 1, 1, '2025-11-24 19:43:32'),
(5, 1, 1, 'да, ха', 0, 1, '2025-11-24 20:19:19'),
(6, 1, NULL, 'ты а?', 1, 1, '2025-11-24 20:33:49'),
(7, 1, NULL, 'короч', 1, 1, '2025-11-24 20:33:53'),
(8, 1, NULL, 'ты лучше не пиши сюда', 1, 1, '2025-11-24 20:34:05'),
(9, 1, 1, 'ку', 0, 1, '2025-11-25 10:00:58'),
(10, 1, 1, 'ха, лох', 0, 1, '2025-11-25 10:07:22'),
(11, 1, 1, 'ку', 0, 1, '2025-11-25 11:03:57'),
(12, 1, 1, 'ку', 1, 1, '2025-11-25 11:07:45'),
(13, 1, 1, 'Что надо', 1, 1, '2025-11-25 11:07:54'),
(14, 1, 1, 'Ты че', 0, 1, '2025-11-25 11:08:02'),
(15, 1, 1, 'лох', 0, 1, '2025-11-25 11:25:56'),
(16, 1, 1, 'фывфывыфв', 1, 1, '2025-11-25 11:26:25'),
(17, 1, 1, 'ку', 1, 1, '2025-11-25 11:37:48'),
(18, 1, 1, 'rasd', 1, 1, '2025-11-25 16:52:54'),
(20, 3, 1, '8', 0, 1, '2025-11-27 19:13:51');

-- --------------------------------------------------------

--
-- Структура таблицы `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` enum('technical','account','billing','suggestion','other') NOT NULL,
  `message` text NOT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `status` enum('open','in-progress','resolved','closed') DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_id`, `ticket_number`, `subject`, `category`, `message`, `contact_email`, `contact_name`, `status`, `priority`, `created_at`, `updated_at`) VALUES
(1, 1, 'TKT-44B122FA', 'ыфвф', 'technical', 'фывфыв', 'dorelore5@gmail.com', 'NihonJin', 'in-progress', 'medium', '2025-11-26 06:46:31', '2025-11-29 17:48:09');

-- --------------------------------------------------------

--
-- Структура таблицы `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_staff` tinyint(1) DEFAULT 0,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `ticket_replies`
--

INSERT INTO `ticket_replies` (`id`, `ticket_id`, `user_id`, `is_staff`, `message`, `created_at`) VALUES
(1, 1, 1, 1, 'п', '2025-11-26 06:57:19'),
(2, 1, 1, 1, 'выа', '2025-11-26 07:08:40'),
(3, 1, 1, 1, 'ыфв', '2025-11-26 07:15:56'),
(4, 1, 1, 0, 'фыв', '2025-11-26 07:19:14'),
(5, 1, 1, 0, 'ку', '2025-11-26 07:23:00'),
(6, 1, 1, 0, 'ычв', '2025-11-26 07:23:31'),
(7, 1, 1, 0, 'ку', '2025-11-26 07:26:51'),
(8, 1, 1, 0, 'фыв', '2025-11-26 07:26:53'),
(9, 1, 1, 0, 'фыв', '2025-11-26 07:26:54'),
(10, 1, 1, 0, 'ыфаф', '2025-11-26 07:26:59'),
(11, 1, 1, 0, 'ку', '2025-11-26 07:30:04'),
(12, 1, 1, 0, 'ыфв', '2025-11-26 07:33:27'),
(13, 1, 1, 0, 'ку', '2025-11-26 07:44:27'),
(14, 1, 1, 0, 'фыв', '2025-11-26 08:12:37'),
(15, 1, 1, 1, 'ку', '2025-11-26 08:14:16'),
(16, 1, 1, 1, 'ку', '2025-11-26 08:14:21'),
(17, 1, 1, 1, 'ку', '2025-11-26 08:17:13'),
(18, 1, 1, 0, 'ку', '2025-11-26 08:17:17'),
(19, 1, 1, 1, 'ку', '2025-11-26 08:21:03'),
(20, 1, 1, 0, 'ку', '2025-11-26 08:21:05'),
(21, 1, 1, 0, 'ку', '2025-11-26 08:21:15'),
(22, 1, 1, 1, 'ку', '2025-11-26 08:21:21'),
(23, 1, 1, 0, 'ку', '2025-11-26 08:37:19'),
(24, 1, 1, 1, 'ку', '2025-11-26 08:37:22'),
(25, 1, 1, 1, 'ку', '2025-11-27 07:34:39'),
(26, 1, 1, 1, 'ку', '2025-11-27 07:40:41'),
(27, 1, 1, 1, 'ку', '2025-11-28 16:11:20'),
(28, 1, 1, 0, 'ку', '2025-11-29 16:40:31'),
(29, 1, 1, 0, 'ку', '2025-11-29 16:40:40'),
(30, 1, 1, 0, 'ку', '2025-11-29 17:05:59'),
(31, 1, 1, 0, 'ку', '2025-11-29 17:15:37'),
(32, 1, 1, 0, 'как дела', '2025-11-29 17:15:43'),
(33, 1, 1, 1, 'ку', '2025-11-29 17:48:09');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','premium','vip','support','admin','high-admin','owner') DEFAULT 'user',
  `status` enum('active','blocked') NOT NULL DEFAULT 'active',
  `blocked_at` datetime DEFAULT NULL,
  `block_reason` varchar(255) DEFAULT NULL,
  `blocked_by` int(11) DEFAULT NULL,
  `blocked_until` datetime DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `profile_updated` tinyint(1) DEFAULT 0,
  `username_changed_at` datetime DEFAULT NULL,
  `notify_order_updates` tinyint(1) NOT NULL DEFAULT 1,
  `notify_support_replies` tinyint(1) NOT NULL DEFAULT 1,
  `profile_visibility` enum('public','members','private') NOT NULL DEFAULT 'public',
  `show_online_status` tinyint(1) NOT NULL DEFAULT 1,
  `session_version` int(11) NOT NULL DEFAULT 1,
  `session_invalidated_at` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_activity` timestamp NULL DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`, `blocked_at`, `block_reason`, `blocked_by`, `blocked_until`, `avatar`, `bio`, `location`, `profile_updated`, `username_changed_at`, `notify_order_updates`, `notify_support_replies`, `profile_visibility`, `show_online_status`, `session_version`, `session_invalidated_at`, `remember_token`, `remember_token_expires`, `created_at`, `updated_at`, `last_activity`, `is_online`) VALUES
(1, 'NihonJin', 'dorelore5@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$VVYxdTF1a3gxSDdsMGNRNw$IX0M//m7O7i6Jd2yjrNnwtxXgPlfnA1qlPv/nbxvLFg', 'owner', 'active', NULL, NULL, NULL, NULL, 'uploads/avatars/avatar_1_1764011349.gif', 'Nihon ni sundeimasu', 'Токио, Япония', 1, NULL, 1, 1, 'public', 1, 1, NULL, NULL, NULL, '2025-11-24 12:52:54', '2025-12-06 13:54:18', '2025-12-06 13:54:17', 1),
(2, 'NihonJin2', 'dorelore6@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$LkF2Sm5HSGgxN3lvelJqRw$RtyfOxDssnS27KtnKjAAxhvO2KEqLE+yjSalRQO3NY4', 'user', 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 1, 1, 'public', 1, 1, NULL, NULL, NULL, '2025-11-29 19:18:21', '2025-11-29 21:03:37', '2025-11-29 20:58:19', 0),
(3, 'NihonJin3', 'dorelore7@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$Ty5FZ3BqV3lpV1RwTEFBLw$krZMoz6UbpPd3X2oSPTOsoM8X3W5FEZXDzBNePqsGCQ', 'user', 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-11-30 16:42:03', 1, 1, 'public', 1, 6, '2025-11-30 21:34:53', NULL, NULL, '2025-11-29 21:04:23', '2025-11-30 19:32:53', '2025-11-30 19:27:40', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `user_block_log`
--

CREATE TABLE `user_block_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `blocked_by` int(11) DEFAULT NULL,
  `reason` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `blocked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_block_log`
--

INSERT INTO `user_block_log` (`id`, `user_id`, `blocked_by`, `reason`, `created_at`, `blocked_until`) VALUES
(1, 2, 1, 'やばい、お前何したんだか?', '2025-11-29 20:28:09', NULL),
(2, 2, 1, 'Разблокирован досрочно модератором', '2025-11-29 20:30:54', NULL),
(3, 2, 1, '何お前はしたんだか、やばあー', '2025-11-29 20:31:26', NULL),
(4, 2, 1, 'Разблокирован досрочно модератором', '2025-11-29 21:00:19', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user_builds`
--

CREATE TABLE `user_builds` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `build_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `purpose` enum('gaming','work','streaming','editing','other') DEFAULT 'other',
  `components` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`components`)),
  `user_session` varchar(255) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `total_power` int(11) DEFAULT NULL,
  `estimated_fps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`estimated_fps`)),
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_builds`
--

INSERT INTO `user_builds` (`id`, `user_id`, `build_name`, `description`, `purpose`, `components`, `user_session`, `total_price`, `total_power`, `estimated_fps`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 1, 'апврап', NULL, 'other', '{\"cpu\":\"Intel Core i9-14900K\",\"gpu\":\"NVIDIA GeForce RTX 5090\",\"ram\":\"Corsair Dominator Platinum RGB 64GB\",\"storage\":\"Crucial P5 Plus 2TB NVMe SSD\",\"psu\":\"Corsair RM1000x\",\"case\":\"Corsair 5000D AIRFLOW\",\"cooling\":\"Arctic Liquid Freezer II 360\",\"components\":{\"cpu\":\"Intel Core i9-14900K\",\"gpu\":\"NVIDIA GeForce RTX 5090\",\"motherboard\":\"MSI PRO B760M-A WIFI\",\"ram\":\"Corsair Dominator Platinum RGB 64GB\",\"storage\":\"Crucial P5 Plus 2TB NVMe SSD\",\"psu\":\"Corsair RM1000x\",\"case\":\"Corsair 5000D AIRFLOW\",\"cooling\":\"Arctic Liquid Freezer II 360\"},\"total_components\":8,\"saved_at\":\"2025-11-24T21:57:31.317Z\"}', '9947c34f812c1327cb70aa700ba21849', 410920.00, 786, NULL, 1, '2025-11-24 21:57:31', '2025-11-24 21:57:31'),
(2, 1, 'Ghost 13371716', NULL, 'other', '{\"cpu\":\"Intel Core i9-14900K\",\"gpu\":\"MSI Gaming X Trio GeForce RTX 4080\",\"ram\":\"Corsair Dominator Platinum RGB 64GB\",\"storage\":\"Компонент\",\"psu\":\"ASUS ROG STRIX 1000W Gold Aura Edition\",\"case\":\"be quiet! Pure Base 500DX\",\"cooling\":\"Arctic Liquid Freezer II 280\",\"components\":{\"cpu\":\"Intel Core i9-14900K\",\"gpu\":\"MSI Gaming X Trio GeForce RTX 4080\",\"motherboard\":\"ASUS PRIME Z790-P WIFI\",\"ram\":\"Corsair Dominator Platinum RGB 64GB\",\"storage\":\"Компонент\",\"psu\":\"ASUS ROG STRIX 1000W Gold Aura Edition\",\"case\":\"be quiet! Pure Base 500DX\",\"cooling\":\"Arctic Liquid Freezer II 280\"},\"total_components\":8,\"saved_at\":\"2025-11-27T19:54:23.442Z\"}', '15b7c43914aff8f0208a5691d7d3d1c7', NULL, 537, NULL, 1, '2025-11-27 19:54:23', '2025-11-27 19:54:23'),
(12, 1, 'TOP GAME', NULL, 'gaming', '{\"cpu\":\"Intel Core i9-14900K\",\"gpu\":\"MSI Gaming X Trio GeForce RTX 4080\",\"ram\":\"Corsair Dominator Platinum RGB 64GB\",\"storage\":\"Компонент\",\"psu\":\"ASUS ROG STRIX 1000W Gold Aura Edition\",\"case\":\"be quiet! Pure Base 500DX\",\"cooling\":\"Arctic Liquid Freezer II 280\",\"components\":{\"cpu\":\"Intel Core i9-14900K\",\"gpu\":\"MSI Gaming X Trio GeForce RTX 4080\",\"motherboard\":\"ASUS PRIME Z790-P WIFI\",\"ram\":\"Corsair Dominator Platinum RGB 64GB\",\"storage\":\"Компонент\",\"psu\":\"ASUS ROG STRIX 1000W Gold Aura Edition\",\"case\":\"be quiet! Pure Base 500DX\",\"cooling\":\"Arctic Liquid Freezer II 280\"},\"total_components\":8,\"saved_at\":\"2025-11-27T20:56:08.404Z\",\"purpose\":\"gaming\"}', 'fef20bc3a78780b284ee969b150bbe83', 283410.00, 549, NULL, 1, '2025-11-27 20:56:08', '2025-11-27 20:56:08');

-- --------------------------------------------------------

--
-- Структура таблицы `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_hash` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `ip_location` varchar(120) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `device` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_seen` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_hash`, `ip_address`, `ip_location`, `user_agent`, `platform`, `browser`, `device`, `created_at`, `last_seen`) VALUES
(7, 3, '683113b240c0c074d71f09077abe502cd185c26222e86859c602a0e82d9fc07e', '::1', 'Локальная сеть', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'Windows', 'Chrome', 'Desktop', '2025-11-30 21:32:17', '2025-11-30 21:32:17'),
(8, 3, '77aefecae34c73934e6b2124cafab8aa7b91d85c23fe3bc48d50d5e62070efc0', '::1', 'Локальная сеть', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'Windows', 'Chrome', 'Desktop', '2025-11-30 21:35:07', '2025-11-30 21:35:07'),
(9, 1, '0afc1509c62e648abb62ccfe6c4d8c25b633493d7882e343c89afb28dff80da2', '::1', 'Локальная сеть', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'Windows', 'Chrome', 'Desktop', '2025-11-30 22:27:56', '2025-11-30 22:27:56'),
(10, 1, '755504ccf27d2e37c8e9bbff343662fc735b0822e5c7f87c66f9f8a70d43362f', '::1', 'Локальная сеть', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'Windows', 'Chrome', 'Desktop', '2025-12-01 18:12:56', '2025-12-01 18:12:56');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `benchmarks`
--
ALTER TABLE `benchmarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_component_game` (`component_id`,`game_id`),
  ADD KEY `benchmarks_ibfk_2` (`game_id`);

--
-- Индексы таблицы `build_comments`
--
ALTER TABLE `build_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_build_id` (`build_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_parent_id` (`parent_id`);

--
-- Индексы таблицы `build_components`
--
ALTER TABLE `build_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `component_id` (`component_id`),
  ADD KEY `idx_build` (`build_id`);

--
-- Индексы таблицы `build_likes`
--
ALTER TABLE `build_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`build_id`,`user_id`),
  ADD KEY `idx_build_id` (`build_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Индексы таблицы `components_case`
--
ALTER TABLE `components_case`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_manufacturer` (`manufacturer`);

--
-- Индексы таблицы `components_cooling`
--
ALTER TABLE `components_cooling`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_manufacturer` (`manufacturer`);

--
-- Индексы таблицы `components_cpu`
--
ALTER TABLE `components_cpu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_manufacturer` (`manufacturer`);

--
-- Индексы таблицы `components_gpu`
--
ALTER TABLE `components_gpu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_manufacturer` (`manufacturer`);

--
-- Индексы таблицы `components_mobo`
--
ALTER TABLE `components_mobo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_manufacturer` (`manufacturer`);

--
-- Индексы таблицы `components_psu`
--
ALTER TABLE `components_psu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_manufacturer` (`manufacturer`);

--
-- Индексы таблицы `components_ram`
--
ALTER TABLE `components_ram`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_manufacturer` (`manufacturer`);

--
-- Индексы таблицы `components_storage`
--
ALTER TABLE `components_storage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_manufacturer` (`manufacturer`);

--
-- Индексы таблицы `component_reviews`
--
ALTER TABLE `component_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_component` (`component_category_id`,`component_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Индексы таблицы `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_status` (`status`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- Индексы таблицы `order_notifications`
--
ALTER TABLE `order_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_notifications_user_id` (`user_id`),
  ADD KEY `idx_order_notifications_order_id` (`order_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_user_type` (`user_id`,`type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Индексы таблицы `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Индексы таблицы `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_ticket_number` (`ticket_number`),
  ADD KEY `idx_status` (`status`);

--
-- Индексы таблицы `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_remember_token` (`remember_token`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_last_activity` (`last_activity`),
  ADD KEY `idx_is_online` (`is_online`),
  ADD KEY `fk_users_blocked_by` (`blocked_by`);

--
-- Индексы таблицы `user_block_log`
--
ALTER TABLE `user_block_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `blocked_by` (`blocked_by`);

--
-- Индексы таблицы `user_builds`
--
ALTER TABLE `user_builds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`user_session`),
  ADD KEY `idx_public` (`is_public`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_purpose` (`purpose`);

--
-- Индексы таблицы `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_session_hash` (`session_hash`),
  ADD KEY `idx_user_sessions_user` (`user_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `benchmarks`
--
ALTER TABLE `benchmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT для таблицы `build_comments`
--
ALTER TABLE `build_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `build_components`
--
ALTER TABLE `build_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT для таблицы `build_likes`
--
ALTER TABLE `build_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `components_case`
--
ALTER TABLE `components_case`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=172;

--
-- AUTO_INCREMENT для таблицы `components_cooling`
--
ALTER TABLE `components_cooling`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- AUTO_INCREMENT для таблицы `components_cpu`
--
ALTER TABLE `components_cpu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT для таблицы `components_gpu`
--
ALTER TABLE `components_gpu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT для таблицы `components_mobo`
--
ALTER TABLE `components_mobo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT для таблицы `components_psu`
--
ALTER TABLE `components_psu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT для таблицы `components_ram`
--
ALTER TABLE `components_ram`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT для таблицы `components_storage`
--
ALTER TABLE `components_storage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT для таблицы `component_reviews`
--
ALTER TABLE `component_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблицы `order_notifications`
--
ALTER TABLE `order_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT для таблицы `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `ticket_replies`
--
ALTER TABLE `ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `user_block_log`
--
ALTER TABLE `user_block_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `user_builds`
--
ALTER TABLE `user_builds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `benchmarks`
--
ALTER TABLE `benchmarks`
  ADD CONSTRAINT `benchmarks_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `components_gpu` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `benchmarks_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `build_comments`
--
ALTER TABLE `build_comments`
  ADD CONSTRAINT `build_comments_ibfk_1` FOREIGN KEY (`build_id`) REFERENCES `user_builds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `build_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comment_parent` FOREIGN KEY (`parent_id`) REFERENCES `build_comments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `build_components`
--
ALTER TABLE `build_components`
  ADD CONSTRAINT `build_components_ibfk_1` FOREIGN KEY (`build_id`) REFERENCES `user_builds` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `build_likes`
--
ALTER TABLE `build_likes`
  ADD CONSTRAINT `build_likes_ibfk_1` FOREIGN KEY (`build_id`) REFERENCES `user_builds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `build_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `components_case`
--
ALTER TABLE `components_case`
  ADD CONSTRAINT `fk_case_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `components_cooling`
--
ALTER TABLE `components_cooling`
  ADD CONSTRAINT `fk_cooling_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `components_cpu`
--
ALTER TABLE `components_cpu`
  ADD CONSTRAINT `fk_cpu_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `components_gpu`
--
ALTER TABLE `components_gpu`
  ADD CONSTRAINT `fk_gpu_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `components_mobo`
--
ALTER TABLE `components_mobo`
  ADD CONSTRAINT `fk_mobo_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `components_psu`
--
ALTER TABLE `components_psu`
  ADD CONSTRAINT `fk_psu_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `components_ram`
--
ALTER TABLE `components_ram`
  ADD CONSTRAINT `fk_ram_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `components_storage`
--
ALTER TABLE `components_storage`
  ADD CONSTRAINT `fk_storage_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `component_reviews`
--
ALTER TABLE `component_reviews`
  ADD CONSTRAINT `fk_component_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `order_notifications`
--
ALTER TABLE `order_notifications`
  ADD CONSTRAINT `fk_order_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `support_messages`
--
ALTER TABLE `support_messages`
  ADD CONSTRAINT `support_messages_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `support_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD CONSTRAINT `ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_blocked_by` FOREIGN KEY (`blocked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `user_block_log`
--
ALTER TABLE `user_block_log`
  ADD CONSTRAINT `user_block_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_block_log_ibfk_2` FOREIGN KEY (`blocked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `user_builds`
--
ALTER TABLE `user_builds`
  ADD CONSTRAINT `user_builds_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
