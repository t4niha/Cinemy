-- DROP ALL TABLES
DROP TABLE IF EXISTS `series_awards`;
DROP TABLE IF EXISTS `movie_awards`;
DROP TABLE IF EXISTS `series_watchlists`;
DROP TABLE IF EXISTS `movie_watchlists`;
DROP TABLE IF EXISTS `watchlists`;
DROP TABLE IF EXISTS `series_reviews`;
DROP TABLE IF EXISTS `movie_reviews`;
DROP TABLE IF EXISTS `acted_in_series`;
DROP TABLE IF EXISTS `acted_in_movies`;
DROP TABLE IF EXISTS `actors`;
DROP TABLE IF EXISTS `admin_picks`;
DROP TABLE IF EXISTS `series_genres`;
DROP TABLE IF EXISTS `movie_genres`;
DROP TABLE IF EXISTS `series`;
DROP TABLE IF EXISTS `movies`;
DROP TABLE IF EXISTS `directors`;
DROP TABLE IF EXISTS `genres`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `user_accounts`;
DROP TABLE IF EXISTS `admin_accounts`;

-- ADMIN ACCOUNTS
CREATE TABLE `admin_accounts` (
  `admin_account_id` int NOT NULL AUTO_INCREMENT,
  `admin_account_email` varchar(60) NOT NULL UNIQUE,
  `username` varchar(60) NOT NULL UNIQUE,
  `password` varchar(60) NOT NULL,
  `verification_code` varchar(6) NULL,
  PRIMARY KEY (`admin_account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- USER ACCOUNTS
CREATE TABLE `user_accounts` (
  `user_account_id` int NOT NULL AUTO_INCREMENT,
  `user_account_email` varchar(60) NOT NULL UNIQUE,
  `username` varchar(60) NOT NULL UNIQUE,
  `password` varchar(60) NOT NULL,
  `subscription_status` ENUM('active', 'inactive') DEFAULT 'inactive',
  `payment_info` varchar(60) DEFAULT NULL,
  `verification_code` varchar(6) NULL,
  PRIMARY KEY (`user_account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- USER PROFILES
CREATE TABLE `user_profiles` (
  `user_profile_id` int NOT NULL AUTO_INCREMENT,
  `user_profile_name` varchar(60) NOT NULL,
  `user_account_id` int NOT NULL,
  PRIMARY KEY (`user_profile_id`),
  CONSTRAINT `user_account_id_fk_user_profiles` FOREIGN KEY (`user_account_id`) REFERENCES `user_accounts` (`user_account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- GENRES
CREATE TABLE `genres` (
  `genre_id` int NOT NULL AUTO_INCREMENT,
  `genre` varchar(60) NOT NULL UNIQUE,
  PRIMARY KEY (`genre_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- DIRECTORS
CREATE TABLE `directors` (
  `director_id` int NOT NULL AUTO_INCREMENT,
  `director_name` varchar(60) NOT NULL,
  PRIMARY KEY (`director_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MOVIES
CREATE TABLE `movies` (
  `movie_id` int NOT NULL AUTO_INCREMENT,
  `movie_title` varchar(60) NOT NULL,
  `movie_link` varchar(800) NOT NULL,
  `movie_description` varchar(1000) DEFAULT NULL,
  `imdb_rating` decimal(3,1) DEFAULT NULL,
  `rotten_tomatoes_rating` int DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `movie_director_id` int DEFAULT NULL,
  PRIMARY KEY (`movie_id`),
  CONSTRAINT `movie_director_id_fk_movies` FOREIGN KEY (`movie_director_id`) REFERENCES `directors` (`director_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- SERIES
CREATE TABLE `series` (
  `series_id` int NOT NULL AUTO_INCREMENT,
  `series_title` varchar(60) DEFAULT NULL,
  `series_link` varchar(800) NOT NULL,
  `series_description` varchar(1000) DEFAULT NULL,
  `imdb_rating` decimal(3,1) DEFAULT NULL,
  `rotten_tomatoes_rating` int DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  PRIMARY KEY (`series_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MOVIE GENRES
CREATE TABLE `movie_genres` (
  `movie_genre_id` int NOT NULL AUTO_INCREMENT,
  `movie_id` int NOT NULL,
  `genre_id` int NOT NULL,
  PRIMARY KEY (`movie_genre_id`),
  CONSTRAINT `movie_id_fk_movie_genres` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`movie_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `genre_id_fk_movie_genres` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`genre_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- SERIES GENRES
CREATE TABLE `series_genres` (
  `series_genre_id` int NOT NULL AUTO_INCREMENT,
  `series_id` int NOT NULL,
  `genre_id` int NOT NULL,
  PRIMARY KEY (`series_genre_id`),
  CONSTRAINT `series_id_fk_series_genres` FOREIGN KEY (`series_id`) REFERENCES `series` (`series_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `genre_id_fk_series_genres` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`genre_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ADMIN PICKS
CREATE TABLE `admin_picks` (
  `admin_pick_id` int NOT NULL AUTO_INCREMENT,
  `admin_pick_type` ENUM('movie', 'series') NOT NULL,
  `movie_id` int DEFAULT NULL,
  `series_id` int DEFAULT NULL,
  `admin_account_id` int NOT NULL,
  `date_added` date NOT NULL,
  `admin_pick_review` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`admin_pick_id`),
  CONSTRAINT `movie_id_fk_admin_picks` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`movie_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_id_fk_admin_picks` FOREIGN KEY (`series_id`) REFERENCES `series` (`series_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `admin_account_id_admin_picks` FOREIGN KEY (`admin_account_id`) REFERENCES `admin_accounts` (`admin_account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ACTORS
CREATE TABLE `actors` (
  `actor_id` int NOT NULL AUTO_INCREMENT,
  `actor_name` varchar(60) NOT NULL,
  PRIMARY KEY (`actor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ACTED IN MOVIES
CREATE TABLE `acted_in_movies` (
  `acted_in_movie_id` int NOT NULL AUTO_INCREMENT,
  `movie_actor_id` int NOT NULL,
  `movie_id` int NOT NULL,
  `role` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`acted_in_movie_id`),
  CONSTRAINT `movie_actor_id_fk_acted_in_movies` FOREIGN KEY (`movie_actor_id`) REFERENCES `actors` (`actor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `movie_id_fk_acted_in_movies` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`movie_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ACTED IN SERIES
CREATE TABLE `acted_in_series` (
  `acted_in_series_id` int NOT NULL AUTO_INCREMENT,
  `series_actor_id` int NOT NULL,
  `series_id` int NOT NULL,
  `role` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`acted_in_series_id`),
  CONSTRAINT `series_actor_id_fk_acted_in_series` FOREIGN KEY (`series_actor_id`) REFERENCES `actors` (`actor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_id_fk_acted_in_series` FOREIGN KEY (`series_id`) REFERENCES `series` (`series_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MOVIE REVIEWS
CREATE TABLE `movie_reviews` (
  `movie_review_id` int NOT NULL AUTO_INCREMENT,
  `user_profile_id` int NOT NULL,
  `movie_id` int NOT NULL,
  `review_text` varchar(1000) DEFAULT NULL,
  `rating` decimal(3,1) NOT NULL,
  `date_added` date NOT NULL,
  `time_added` time NOT NULL,
  PRIMARY KEY (`movie_review_id`),
  CONSTRAINT `user_profile_id_fk_movie_reviews` FOREIGN KEY (`user_profile_id`) REFERENCES `user_profiles` (`user_profile_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `movie_id_fk_movie_reviews` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`movie_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- SERIES REVIEWS
CREATE TABLE `series_reviews` (
  `series_review_id` int NOT NULL AUTO_INCREMENT,
  `user_profile_id` int NOT NULL,
  `series_id` int NOT NULL,
  `review_text` varchar(1000) DEFAULT NULL,
  `rating` decimal(3,1) NOT NULL,
  `date_added` date NOT NULL,
  `time_added` time NOT NULL,
  PRIMARY KEY (`series_review_id`),
  CONSTRAINT `user_profile_id_fk_series_reviews` FOREIGN KEY (`user_profile_id`) REFERENCES `user_profiles` (`user_profile_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_id_fk_series_reviews` FOREIGN KEY (`series_id`) REFERENCES `series` (`series_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- WATCHLISTS
CREATE TABLE `watchlists` (
  `list_id` int NOT NULL AUTO_INCREMENT,
  `user_profile_id` int NOT NULL,
  `list_type` ENUM('movies', 'series') NOT NULL,
  PRIMARY KEY (`list_id`),
  CONSTRAINT `user_profile_id_fk_watchlists` FOREIGN KEY (`user_profile_id`) REFERENCES `user_profiles` (`user_profile_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MOVIE WATCHLISTS
CREATE TABLE `movie_watchlists` (
  `movie_watchlist_id` int NOT NULL,
  `movie_id` int NOT NULL,
  `time_added` time NOT NULL,
  `date_added` date NOT NULL,
  PRIMARY KEY (`movie_watchlist_id`, `movie_id`),
  CONSTRAINT `movie_watchlist_id_fk_movie_watchlists` FOREIGN KEY (`movie_watchlist_id`) REFERENCES `watchlists` (`list_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `movie_id_fk_movie_watchlists` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`movie_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- SERIES WATCHLISTS
CREATE TABLE `series_watchlists` (
  `series_watchlist_id` int NOT NULL,
  `series_id` int NOT NULL,
  `time_added` time NOT NULL,
  `date_added` date NOT NULL,
  PRIMARY KEY (`series_watchlist_id`, `series_id`),
  CONSTRAINT `series_watchlist_id_fk_series_watchlists` FOREIGN KEY (`series_watchlist_id`) REFERENCES `watchlists` (`list_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `series_id_fk_series_watchlists` FOREIGN KEY (`series_id`) REFERENCES `series` (`series_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MOVIE AWARDS
CREATE TABLE `movie_awards` (
  `movie_award_id` int NOT NULL AUTO_INCREMENT,
  `movie_id` int NOT NULL,
  `award_org` varchar(60) NOT NULL,
  `award_title` varchar(60) NOT NULL,
  `award_status` ENUM('Won', 'Nominated') DEFAULT 'Nominated',
  PRIMARY KEY (`movie_award_id`),
  CONSTRAINT `movie_id_fk_movie_awards` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`movie_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- SERIES AWARDS
CREATE TABLE `series_awards` (
  `series_award_id` int NOT NULL AUTO_INCREMENT,
  `series_id` int NOT NULL,
  `award_org` varchar(60) NOT NULL,
  `award_title` varchar(60) NOT NULL,
  `award_status` ENUM('Won', 'Nominated') DEFAULT 'Nominated',
  PRIMARY KEY (`series_award_id`),
  CONSTRAINT `series_id_fk_series_awards` FOREIGN KEY (`series_id`) REFERENCES `series` (`series_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- GENRES
INSERT INTO `genres` (`genre`)
VALUES 
    ('Comedy'),         -- 1
    ('Action'),         -- 2
    ('Horror'),         -- 3
    ('Drama'),          -- 4
    ('Romance'),        -- 5
    ('Sci-fi'),         -- 6
    ('Western'),        -- 7
    ('Animation'),      -- 8
    ('Thriller'),       -- 9
    ('Fantasy'),        -- 10
    ('Adventure'),      -- 11
    ('Mystery'),        -- 12
    ('Crime'),          -- 13
    ('Reality'),        -- 14
    ('Musical'),        -- 15
    ('Family'),         -- 16
    ('Teen'),           -- 17
    ('Superhero'),      -- 18
    ('War'),            -- 19
    ('Biopic'),         -- 20
    ('Sport'),          -- 21
    ('Noir'),           -- 22
    ('Documentary'),    -- 23
    ('History'),        -- 24
    ('Nature');         -- 25

-- DIRECTORS
INSERT INTO `directors` (`director_name`)
VALUES
    ('Joe Wright'),            -- Pride and Prejudice
    ('Denis Villeneuve'),      -- Blade Runner 2049
    ('Quentin Tarantino');     -- Django Unchained

-- MOVIES
INSERT INTO `movies` (`movie_title`, `movie_link`, `movie_description`, `imdb_rating`, `rotten_tomatoes_rating`, `release_date`, `movie_director_id`)
VALUES 
    ('Pride and Prejudice', 'https://sflix.to/watch-movie/free-pride-and-prejudice-hd-18734.5299120', 'A love story in 19th-century England between Elizabeth Bennet and Mr Darcy.', 7.8, 86, '2005-11-11', 1),
    ('Blade Runner 2049', 'https://sflix.to/watch-movie/free-blade-runner-2049-hd-19739.5352040', 'A new blade runner discovers secrets that challenge humanitys future.', 8.0, 88, '2017-10-06', 2),
    ('Django Unchained', 'https://sflix.to/movie/free-django-unchained-hd-19493', 'A freed slave teams up with a bounty hunter to rescue his wife.', 8.4, 87, '2012-12-25', 3);

-- SERIES
INSERT INTO `series` (`series_title`, `series_link`, `series_description`, `imdb_rating`, `rotten_tomatoes_rating`, `release_date`)
VALUES
    ('Avatar: The Last Airbender', 'https://sflix.to/watch-tv/free-avatar-the-last-airbender-hd-38893.4977640', 'In a war-torn world of elemental magic, a young boy reawakens to undertake a dangerous quest.', 9.3, 100, '2005-02-21'),
    ('True Detective', 'https://sflix.to/watch-tv/free-true-detective-hd-39487.4866403', 'Seasonal anthology series about crime and justice in different parts of the US.', 9.0, 79, '2014-01-12');

-- MOVIE GENRES
INSERT INTO `movie_genres` (`movie_id`, `genre_id`) VALUES
    (1, 5), (1, 4), (1, 24),   -- Pride and Prejudice
    (2, 6), (2, 2), (2, 9),    -- Blade Runner 2049
    (3, 7), (3, 2), (3, 4);    -- Django Unchained

-- SERIES GENRES
INSERT INTO `series_genres` (`series_id`, `genre_id`) VALUES
    (1, 8), (1, 11), (1, 16),   -- Avatar: The Last Airbender
    (2, 4), (2, 12), (2, 13);   -- True Detective

-- ACTORS
INSERT INTO `actors` (`actor_name`) VALUES 
    ('Keira Knightley'),
    ('Matthew Macfadyen'),
    ('Ryan Gosling'),
    ('Harrison Ford'), 
    ('Jamie Foxx'), 
    ('Christoph Waltz'), 
    ('Zach Tyler Eisen'), 
    ('Mae Whitman'),
    ('Woody Harrelson'),
    ('Matthew McConaughey'); 

-- ACTED IN MOVIES
INSERT INTO `acted_in_movies` (`movie_actor_id`, `movie_id`, `role`)
VALUES
    (1, 1, 'Elizabeth Bennet'),
    (2, 1, 'Mr. Darcy'),
    (3, 2, 'K'),
    (4, 2, 'Rick Deckard'),
    (5, 3, 'Django Freeman'),
    (6, 3, 'Dr. King Schultz');

-- ACTED IN SERIES
INSERT INTO `acted_in_series` (`series_actor_id`, `series_id`, `role`)
VALUES
    (7, 1, 'Aang'),
    (8, 1, 'Katara'),
    (9, 2, 'Rust Cohle'),
    (10, 2, 'Marty Hart');

-- MOVIE AWARDS (Randomly Generated)
INSERT INTO `movie_awards` (`movie_id`, `award_org`, `award_title`, `award_status`) VALUES
    (1, 'Academy Awards', 'Best Picture', 'Won'),
    (2, 'Golden Globe Awards', 'Best Actor', 'Nominated'),
    (3, 'BAFTA Awards', 'Best Director', 'Won');

-- SERIES AWARDS (Randomly Generated)
INSERT INTO `series_awards` (`series_id`, `award_org`, `award_title`, `award_status`) VALUES
    (1, 'Golden Globe Awards', 'Best Ensemble Performance', 'Nominated'),
    (2, 'Primetime Emmy Awards', 'Outstanding Direction', 'Won');