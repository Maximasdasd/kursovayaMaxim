SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
       slug VARCHAR(64) NOT NULL UNIQUE,
       name VARCHAR(100) NOT NULL,
       description VARCHAR(400) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
       category_id INT NOT NULL,
       name VARCHAR(140) NOT NULL,
       description TEXT NOT NULL,
    FOREIGN KEY(category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS variants (
    id INT PRIMARY KEY AUTO_INCREMENT,
       product_id INT NOT NULL,
       sku VARCHAR(48) NOT NULL UNIQUE,
       slug VARCHAR(150) NOT NULL UNIQUE,
    option_name VARCHAR(80) NOT NULL,
       format_name VARCHAR(80) NOT NULL,
       unit VARCHAR(32) NOT NULL,
       coverage DECIMAL(8,3) NULL,
    price INT UNSIGNED NOT NULL,
       stock INT UNSIGNED NOT NULL,
       image VARCHAR(80) NOT NULL,
       visible TINYINT NOT NULL DEFAULT 1,
    FOREIGN KEY(product_id) REFERENCES products(id), INDEX(visible,price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
       email VARCHAR(190) NOT NULL UNIQUE,
       name VARCHAR(100) NOT NULL,
       password_hash VARCHAR(255) NOT NULL,
       role VARCHAR(12) NOT NULL DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
       number VARCHAR(24) NOT NULL UNIQUE,
       request_token CHAR(48) NOT NULL UNIQUE,
    user_id INT NULL,
       name VARCHAR(100) NOT NULL,
       phone VARCHAR(32) NOT NULL,
       total BIGINT UNSIGNED NOT NULL,
       status VARCHAR(30) NOT NULL DEFAULT 'Новый',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
       order_id INT NOT NULL,
       variant_id INT NOT NULL,
       sku VARCHAR(48) NOT NULL,
    name VARCHAR(240) NOT NULL,
       unit VARCHAR(32) NOT NULL,
       price INT UNSIGNED NOT NULL,
       quantity INT UNSIGNED NOT NULL,
    FOREIGN KEY(order_id) REFERENCES orders(id), FOREIGN KEY(variant_id) REFERENCES variants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

START TRANSACTION;
INSERT INTO categories(id,slug,name,description) VALUES
(1,'fanera','Фанера','Берёзовые листы для мебели, настилов и внутренней отделки. Выберите толщину и формат.'),
(2,'plitka','Плитка','Выберите фактуру и формат. В карточке можно рассчитать количество упаковок для вашей площади.'),
(3,'klej','Плиточный клей','Сухие смеси для укладки плитки. Цена указана за мешок выбранной фасовки.');
INSERT INTO products(id,category_id,name,description) VALUES
(1,1,'Фанера берёзовая ФК','Листы с выраженным рисунком шпона для внутренних работ. Выберите толщину под конструкцию и проверьте формат листа.'),
(2,1,'Фанера шлифованная','Светлый древесный рисунок и шлифованная поверхность для открытых мебельных деталей и декоративных панелей.'),
(3,2,'Травертин','Тёплая бежевая поверхность с полосами и порами в рисунке камня. Матовый керамогранит для спокойной, цельной отделки.'),
(4,2,'Графит','Тёмно-серая матовая плитка с мелкозернистой фактурой бетона. Квадратный формат для ровной геометрической раскладки.'),
(5,2,'Шалфей','Глазурованная настенная плитка с мягкой волной и приглушённым зелёным оттенком. Кромка подчёркивает ритм кладки.'),
(6,3,'Клей базовый','Сухая клеевая смесь для укладки керамической плитки на подготовленное минеральное основание.'),
(7,3,'Клей эластичный','Сухая эластичная смесь для укладки плитки и керамогранита. Поставляется в бумажном мешке.'),
(8,3,'Клей белый','Белая смесь для светлой облицовки и небольших участков. Компактная фасовка удобна для локального ремонта.');
INSERT INTO variants(product_id,sku,slug,option_name,format_name,unit,coverage,price,stock,image) VALUES
(1,'FK-12-1525','fanera-fk-12-1525','12 мм','1525 × 1525 мм','лист',NULL,149000,32,'plywood'),
(1,'FK-18-1525','fanera-fk-18-1525','18 мм','1525 × 1525 мм','лист',NULL,219000,18,'plywood-light'),
(2,'FS-15-1525','fanera-shlif-15','15 мм','1525 × 1525 мм','лист',NULL,189000,13,'plywood-light'),
(2,'FS-21-1525','fanera-shlif-21','21 мм','1525 × 1525 мм','лист',NULL,279000,0,'plywood'),
(3,'TR-6060','travertin-60','Матовая','60 × 60 см','упаковка',1.440,249000,27,'tile-travertine'),
(4,'GR-6060','grafit-60','Матовая','60 × 60 см','упаковка',1.440,229000,19,'tile-graphite'),
(5,'SG-1020','shalfei-10-20','Глянцевая','10 × 20 см','упаковка',1.000,169000,15,'tile-sage'),
(6,'KL-B25','klej-bazovyi-25','25 кг','Базовый','мешок',NULL,49000,42,'glue-basic'),
(7,'KL-E25','klej-elastichnyi-25','25 кг','Эластичный','мешок',NULL,89000,21,'glue-flex'),
(8,'KL-W05','klej-belyi-5','5 кг','Белый','мешок',NULL,39000,12,'glue-white');

INSERT INTO products(id,category_id,name,description) VALUES
(9,1,'Фанера берёзовая ФСФ','Берёзовый шпон с выразительным рисунком и полосатым торцом. Прямоугольный лист 2440 × 1220 мм удобно раскраивать на длинные детали.'),
(10,1,'Фанера мебельная Ш2','Обе стороны листа отшлифованы. Светлая берёзовая поверхность подходит для задних стенок, деталей ящиков и декоративных панелей.'),
(11,2,'Терраццо','Светлый матовый керамогранит с рисунком каменной крошки. Бежевые, графитовые и пудровые вкрапления складываются в нерегулярный узор.'),
(12,2,'Терракота','Квадратный керамогранит под обожжённую глину. Мелкое зерно и мягкие переходы охристого цвета хорошо видны вблизи.'),
(13,3,'Клей универсальный','Серая цементная смесь для облицовки стен и пола керамической плиткой. Небольшую фасовку удобно брать для ремонта отдельного участка.'),
(14,3,'Клей быстротвердеющий','Смесь с ускоренным схватыванием для укладки плитки небольшими участками. Сухой состав поставляется в бумажном мешке.');

INSERT INTO variants(product_id,sku,slug,option_name,format_name,unit,coverage,price,stock,image) VALUES
(9,'FSF-12-2440','fanera-fsf-12-2440','12 мм','2440 × 1220 мм','лист',NULL,249000,17,'plywood'),
(9,'FSF-18-2440','fanera-fsf-18-2440','18 мм','2440 × 1220 мм','лист',NULL,349000,11,'plywood'),
(9,'FSF-24-2440','fanera-fsf-24-2440','24 мм','2440 × 1220 мм','лист',NULL,469000,8,'plywood'),
(10,'MSh-06-1525','fanera-mebelnaya-6','6 мм','1525 × 1525 мм','лист',NULL,99000,24,'plywood-light'),
(10,'MSh-09-1525','fanera-mebelnaya-9','9 мм','1525 × 1525 мм','лист',NULL,139000,20,'plywood-light'),
(10,'MSh-15-1525','fanera-mebelnaya-15','15 мм','1525 × 1525 мм','лист',NULL,229000,14,'plywood-light'),
(11,'TZ-6060','terracco-60','Матовая','60 × 60 см','упаковка',1.440,289000,18,'tile-terrazzo'),
(11,'TZ-8080','terracco-80','Матовая','80 × 80 см','упаковка',1.280,329000,12,'tile-terrazzo'),
(12,'CT-2020','terrakota-20','Матовая','20 × 20 см','упаковка',1.000,159000,23,'tile-cotto'),
(12,'CT-3030','terrakota-30','Матовая','30 × 30 см','упаковка',1.080,179000,21,'tile-cotto'),
(5,'SG-1530','shalfei-15-30','Глянцевая','15 × 30 см','упаковка',0.900,179000,16,'tile-sage'),
(13,'KL-U05','klej-universalnyi-5','5 кг','Универсальный','мешок',NULL,26900,32,'glue-universal'),
(13,'KL-U10','klej-universalnyi-10','10 кг','Универсальный','мешок',NULL,42900,23,'glue-universal'),
(13,'KL-U25','klej-universalnyi-25','25 кг','Универсальный','мешок',NULL,74900,28,'glue-universal'),
(14,'KL-R10','klej-bystryi-10','10 кг','Быстротвердеющий','мешок',NULL,64900,17,'glue-rapid'),
(14,'KL-R25','klej-bystryi-25','25 кг','Быстротвердеющий','мешок',NULL,129000,10,'glue-rapid');

INSERT INTO users(email,name,password_hash,role) VALUES
('admin@sloy.local','Администратор','$2y$10$ScsQvTseoXu9hUPlgYQ/z.X4/Obfm6FwqcyAWdMdWXbWZX9id6LUu','admin');
COMMIT;
