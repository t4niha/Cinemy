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


-- ADMIN ACCOUNTS
INSERT INTO `admin_accounts` (`admin_account_email`, `username`, `password`)
VALUES 
    ('jene@gmail.com', 'jene', 'je1234'),
    ('john@gmail.com', 'john', 'jn1234'),
    ('lisa@gmail.com', 'lisa', 'la1234'),
    ('luke@gmail.com', 'luke', 'le1234');

-- USER ACCOUNTS
INSERT INTO `user_accounts` (`user_account_email`, `username`, `password`, `subscription_status`, `payment_info`)
VALUES 
    ('niki@gmail.com', 'niki', 'ni1234', 'active', 'visa'),
    ('nate@gmail.com', 'nate', 'ne1234', 'inactive', 'mastercard'),
    ('amy@gmail.com', 'amy', 'ay1234', 'inactive', 'mastercard'),
    ('andy@gmail.com', 'andy', 'ay1234', 'active', 'visa');

-- USER PROFILES
INSERT INTO `user_profiles` (`user_profile_name`, `user_account_id`)
VALUES 
    ('Julian', '1'),
    ('Nick', '1'),
    ('Nikolai', '2'),
    ('Fabrizio', '2'),
    ('Albert', '3'),
    ('Alex', '3'),
    ('Miles', '4'),
    ('Charlie', '4');

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
    ('Greg Mottola'),          -- Superbad
    ('Todd Phillips'),         -- The Hangover
    ('George Miller'),         -- Mad Max: Fury Road
    ('Chad Stahelski'),        -- John Wick
    ('Jordan Peele'),          -- Get Out
    ('John Krasinski'),        -- A Quiet Place
    ('Frank Darabont'),        -- The Shawshank Redemption
    ('Robert Zemeckis'),       -- Forrest Gump
    ('Joe Wright'),            -- Pride and Prejudice
    ('Nick Cassavetes'),       -- The Notebook
    ('Christopher Nolan'),     -- Interstellar
    ('Denis Villeneuve'),      -- Blade Runner 2049
    ('Sergio Leone'),          -- The Good, the Bad and the Ugly
    ('Quentin Tarantino'),     -- Django Unchained
    ('John Lasseter'),         -- Toy Story
    ('Hayao Miyazaki'),        -- Spirited Away
    ('David Fincher'),         -- Se7en
    ('David Fincher'),         -- Gone Girl
    ('Peter Jackson'),         -- The Lord of the Rings: The Fellowship of the Ring
    ('Chris Columbus'),        -- Harry Potter and the Sorcerers Stone
    ('Steven Spielberg'),      -- Indiana Jones and the Raiders of the Lost Ark
    ('Alejandro G. Iñárritu'), -- The Revenant
    ('David Fincher'),         -- The Girl with the Dragon Tattoo
    ('Martin Scorsese'),       -- Shutter Island
    ('Steven Bognar'),         -- American Factory
    ('Jimmy Chin'),            -- Free Solo
    ('Damien Chazelle'),       -- La La Land
    ('Michael Gracey'),        -- The Greatest Showman
    ('Roger Allers'),          -- The Lion King
    ('Chris Buck'),            -- Frozen
    ('John Hughes'),           -- The Breakfast Club
    ('Amy Heckerling'),        -- Clueless
    ('Christopher Nolan'),     -- The Dark Knight
    ('Anthony Russo'),         -- Avengers: Endgame
    ('Steven Spielberg'),      -- Saving Private Ryan
    ('Sam Mendes'),            -- 1917
    ('David Fincher'),         -- The Social Network
    ('Bryan Singer'),          -- Bohemian Rhapsody
    ('John G. Avildsen'),      -- Rocky
    ('Boaz Yakin'),            -- Remember the Titans
    ('Roman Polanski'),        -- Chinatown
    ('Billy Wilder'),          -- Double Indemnity
    ('Ava DuVernay'),          -- 13th
    ('Luc Jacquet');           -- March of the Penguins

-- MOVIES
INSERT INTO `movies` (`movie_title`, `movie_link`, `movie_description`, `imdb_rating`, `rotten_tomatoes_rating`, `release_date`, `movie_director_id`)
VALUES 
    ('Superbad', 'https://sflix.to/watch-movie/free-superbad-hd-18729.5349763', 'Two high school friends navigate a wild night of parties and mishaps.', 7.6, 88, '2007-08-17', 1),
    ('The Hangover', 'https://sflix.to/watch-movie/free-the-hangover-hd-19008.5298652', 'Three friends try to piece together the events of a bachelor party gone wrong.', 7.7, 78, '2009-06-05', 2),
    ('Mad Max: Fury Road', 'https://sflix.to/watch-movie/free-mad-max-fury-road-hd-19677.5349145', 'In a post-apocalyptic wasteland Max helps a group of rebels escape a tyrant.', 8.1, 97, '2015-05-15', 3),
    ('John Wick', 'https://sflix.to/watch-movie/free-john-wick-hd-19789.5297287', 'An ex-hitman seeks vengeance for his stolen car and slain dog.', 7.4, 86, '2014-10-24', 4),
    ('Get Out', 'https://sflix.to/watch-movie/free-get-out-hd-19550.5297698', 'A young man discovers sinister secrets during a visit to his girlfriends family.', 7.8, 98, '2017-02-24', 5),
    ('A Quiet Place', 'https://sflix.to/watch-movie/free-a-quiet-place-hd-19740.5297305', 'A family must live in silence to evade deadly creatures hunting by sound.', 7.5, 96, '2018-04-06', 6),
    ('The Shawshank Redemption', 'https://sflix.to/watch-movie/free-the-shawshank-redemption-hd-19679.5349142', 'Two imprisoned men bond over years finding solace and redemption.', 9.3, 91, '1994-10-14', 7),
    ('Forrest Gump', 'https://sflix.to/watch-movie/free-forrest-gump-hd-19710.5297422', 'The extraordinary life journey of a simple kind-hearted man.', 8.8, 95, '1994-07-06', 8),
    ('Pride and Prejudice', 'https://sflix.to/watch-movie/free-pride-and-prejudice-hd-18734.5299120', 'A love story in 19th-century England between Elizabeth Bennet and Mr Darcy.', 7.8, 86, '2005-11-11', 9),
    ('The Notebook', 'https://sflix.to/watch-movie/free-the-notebook-hd-19489.5349100', 'The story of a young couples love enduring through hardships.', 7.8, 53, '2004-06-25', 10),
    ('Interstellar', 'https://sflix.to/watch-movie/free-interstellar-hd-19788.5297302', 'A team of explorers travels through a wormhole to ensure humanitys survival.', 8.6, 72, '2014-11-07', 11),
    ('Blade Runner 2049', 'https://sflix.to/watch-movie/free-blade-runner-2049-hd-19739.5352040', 'A new blade runner discovers secrets that challenge humanitys future.', 8.0, 88, '2017-10-06', 12),
    ('The Good the Bad and the Ugly', 'https://sflix.to/watch-movie/free-the-good-the-bad-and-the-ugly-hd-19502.5297758', 'A bounty hunter and two others vie for a hidden fortune during the Civil War.', 8.8, 97, '1966-12-29', 13),
    ('Django Unchained', 'https://sflix.to/movie/free-django-unchained-hd-19493', 'A freed slave teams up with a bounty hunter to rescue his wife.', 8.4, 87, '2012-12-25', 14),
    ('Toy Story', 'https://sflix.to/watch-movie/free-toy-story-hd-19538.5349217', 'A cowboy doll feels replaced when a space ranger arrives.', 8.3, 100, '1995-11-22', 15),
    ('Spirited Away', 'https://sflix.to/watch-movie/free-spirited-away-hd-19488.5349253', 'A girl enters a magical world ruled by gods witches and spirits.', 8.6, 97, '2001-07-20', 16),
    ('Se7en', 'https://sflix.to/watch-movie/free-se7en-hd-19726.5349019', 'Two detectives hunt a serial killer targeting victims based on the seven deadly sins.', 8.6, 82, '1995-09-22', 17),
    ('Gone Girl', 'https://sflix.to/watch-movie/free-gone-girl-hd-19717.5297395', 'A man becomes the prime suspect in his wifes disappearance.', 8.1, 88, '2014-10-03', 18),
    ('The Lord of the Rings: The Fellowship of the Ring', 'https://sflix.to/watch-movie/free-the-lord-of-the-rings-the-fellowship-of-the-ring-hd-19736.5297329', 'A hobbit and his companions embark on a quest to destroy the One Ring.', 8.8, 91, '2001-12-19', 19),
    ('Harry Potter and the Philosophers Stone', 'https://sflix.to/watch-movie/free-harry-potter-and-the-philosophers-stone-hd-19812.5352007', 'A boy discovers he is a wizard and begins his magical education.', 7.6, 81, '2001-11-16', 20),
    ('Indiana Jones and the Raiders of the Lost Ark', 'https://sflix.to/watch-movie/free-raiders-of-the-lost-ark-hd-19406.5297932', 'An archaeologist races against the Nazis to find the Ark of the Covenant.', 8.4, 95, '1981-06-12', 21),
    ('The Revenant', 'https://sflix.to/watch-movie/free-the-revenant-hd-19646.5297491', 'A frontiersman fights for survival and revenge after being left for dead.', 8.0, 78, '2015-12-25', 22),
    ('The Girl with the Dragon Tattoo', 'https://sflix.to/movie/free-the-girl-with-the-dragon-tattoo-hd-19632', 'A journalist and hacker team up to solve a decades-old disappearance.', 7.8, 86, '2011-12-20', 23),
    ('Shutter Island', 'https://sflix.to/watch-movie/free-shutter-island-hd-19490.5349259', 'A detective investigates a disappearance at a remote psychiatric hospital.', 8.2, 68, '2010-02-19', 24),
    ('American Factory', 'https://sflix.to/watch-movie/free-american-factory-hd-7775.5512975', 'A Chinese billionaire reopens an abandoned factory in the US creating a clash of cultures.', 7.4, 96, '2019-08-21', 25),
    ('Free Solo', 'https://sflix.to/watch-movie/free-free-solo-hd-62931.5467390', 'A climber attempts to scale El Capitan without ropes.', 8.2, 97, '2018-09-28', 26),
    ('La La Land', 'https://sflix.to/watch-movie/free-la-la-land-hd-19613.5297566', 'A jazz musician and an aspiring actress navigate love and ambition.', 8.0, 91, '2016-12-25', 27),
    ('The Greatest Showman', 'https://sflix.to/watch-movie/free-the-greatest-showman-hd-19727.5349031', 'The story of PT Barnum and his creation of the Barnum and Bailey Circus.', 7.6, 57, '2017-12-20', 28),
    ('The Lion King', 'https://sflix.to/watch-movie/free-the-lion-king-hd-19472.5297812', 'A lion cub flees his kingdom only to learn the importance of responsibility and bravery.', 8.5, 93, '1994-06-15', 29),
    ('Frozen', 'https://sflix.to/watch-movie/free-frozen-hd-19753.5297356', 'A young woman must save her kingdom from eternal winter with the help of her sister.', 7.4, 90, '2013-11-27', 30),
    ('The Breakfast Club', 'https://sflix.to/watch-movie/free-the-breakfast-club-hd-17616.5301115', 'Five high school students discover they have more in common than they thought.', 7.8, 89, '1985-02-15', 31),
    ('Clueless', 'https://sflix.to/watch-movie/free-clueless-hd-17414.5301523', 'A wealthy teenager navigates love and friendship in high school.', 6.9, 81, '1995-07-19', 32),
    ('The Dark Knight', 'https://sflix.to/watch-movie/free-the-dark-knight-hd-19752.5297326', 'Batman battles the Joker as chaos engulfs Gotham City.', 9.0, 94, '2008-07-18', 33),
    ('Avengers: Endgame', 'https://sflix.to/watch-movie/free-avengers-endgame-hd-19722.5376856', 'The Avengers assemble one last time to reverse the damage caused by Thanos.', 8.4, 94, '2019-04-26', 34),
    ('Saving Private Ryan', 'https://sflix.to/movie/free-saving-private-ryan-hd-19481', 'A group of soldiers embarks on a mission to rescue a paratrooper during World War II.', 8.6, 93, '1998-07-24', 35),
    ('1917', 'https://sflix.to/watch-movie/free-1917-hd-41773.5417827', 'Two soldiers are tasked with delivering a critical message during World War I.', 8.3, 89, '2019-12-25', 36),
    ('The Social Network', 'https://sflix.to/watch-movie/free-the-social-network-hd-18956.5298736', 'The story of the creation of Facebook and its founder Mark Zuckerberg.', 7.7, 96, '2010-10-01', 37),
    ('Bohemian Rhapsody', 'https://sflix.to/watch-movie/free-bohemian-rhapsody-hd-19836.5297143', 'The rise of Queen and Freddie Mercury leading up to their iconic Live Aid performance.', 7.9, 60, '2018-11-02', 38),
    ('Rocky', 'https://sflix.to/watch-movie/free-rocky-hd-19528.5297704', 'A small-time boxer gets a shot at the world heavyweight championship.', 8.1, 93, '1976-11-21', 39),
    ('Remember the Titans', 'https://sflix.to/watch-movie/free-remember-the-titans-hd-17383.5301592', 'The inspiring story of a high school football team overcoming racial tensions.', 7.8, 73, '2000-09-29', 40),
    ('Chinatown', 'https://sflix.to/watch-movie/free-chinatown-hd-17353.5350465', 'A private investigator unravels a web of deceit and corruption in Los Angeles.', 8.2, 99, '1974-06-20', 41),
    ('Double Indemnity', 'https://sflix.to/watch-movie/free-double-indemnity-hd-16988.5302162', 'An insurance agent is drawn into a murder-for-insurance plot.', 8.3, 97, '1944-09-06', 42),
    ('13th', 'https://sflix.to/watch-movie/free-13th-hd-10216.5359630', 'The history of racial inequality in the United States, with the nations prisons disproportionately filled with African-Americans.', 8.2, 97, '2016-10-07', 43),
    ('March of the Penguins', 'https://sflix.to/watch-movie/free-march-of-the-penguins-hd-12806.5312332', 'A look at the journey of emperor penguins to their breeding grounds.', 7.5, 94, '2005-06-24', 44);

-- SERIES
INSERT INTO `series` (`series_title`, `series_link`, `series_description`, `imdb_rating`, `rotten_tomatoes_rating`, `release_date`)
VALUES
    ('The Office', 'https://sflix.to/watch-tv/free-the-office-hd-39383.4891918', 'A mockumentary on a group of typical office workers where the workday consists of ego clashes and inappropriate behavior.', 9.0, 81, '2005-03-24'),
    ('Breaking Bad', 'https://sflix.to/watch-tv/free-breaking-bad-hd-39506.4858942', 'A high school chemistry teacher turned methamphetamine producer partners with a former student.', 9.5, 96, '2008-01-20'),
    ('Stranger Things', 'https://sflix.to/watch-tv/free-beyond-stranger-things-hd-34530.5227441', 'A group of friends uncovers supernatural forces and secret experiments in their small town.', 8.7, 92, '2016-07-15'),
    ('The Crown', 'https://sflix.to/watch-tv/free-the-crown-hd-38996.4959457', 'The series chronicles the reign of Queen Elizabeth II.', 8.6, 91, '2016-11-04'),
    ('Outlander', 'https://sflix.to/watch-tv/free-outlander-hd-39513.4856443', 'A married World War II nurse is mysteriously transported to 1743 Scotland where she finds adventure and romance.', 8.4, 88, '2014-08-09'),
    ('The Mandalorian', 'https://sflix.to/watch-tv/free-the-mandalorian-hd-32386.5252932', 'A lone bounty hunter explores the outer reaches of the galaxy far from the authority of the New Republic.', 8.7, 93, '2019-11-12'),
    ('Yellowstone', 'https://sflix.to/watch-tv/free-yellowstone-hd-38684.5002564', 'A ranching family struggles to maintain their land and way of life.', 8.7, 90, '2018-06-20'),
    ('Avatar: The Last Airbender', 'https://sflix.to/watch-tv/free-avatar-the-last-airbender-hd-38893.4977640', 'In a war-torn world of elemental magic, a young boy reawakens to undertake a dangerous quest.', 9.3, 100, '2005-02-21'),
    ('Mindhunter', 'https://sflix.to/watch-tv/free-mindhunter-hd-39346.4898518', 'Two FBI agents interview imprisoned serial killers to understand their psychology.', 8.6, 96, '2017-10-13'),
    ('Game of Thrones', 'https://sflix.to/watch-tv/free-game-of-thrones-hd-39539.4846588', 'Nine noble families wage war against each other to claim the Iron Throne.', 9.2, 89, '2011-04-17'),
    ('Lost', 'https://sflix.to/watch-tv/free-lost-hd-39427.4880854', 'Survivors of a plane crash must work together to survive on a mysterious island.', 8.3, 85, '2004-09-22'),
    ('Twin Peaks', 'https://sflix.to/watch-tv/free-twin-peaks-hd-39237.4920997', 'An eccentric FBI agent investigates the murder of a young woman in the quirky town of Twin Peaks.', 8.8, 92, '1990-04-08'),
    ('The Sopranos', 'https://sflix.to/watch-tv/free-the-sopranos-hd-39319.4903615', 'New Jersey mob boss Tony Soprano deals with personal and professional issues in his home and business life.', 9.2, 92, '1999-01-10'),
    ('Survivor', 'https://sflix.to/watch-tv/free-survivor-hd-39437.4877692', 'Contestants are stranded in a remote location and must build alliances and compete in challenges to avoid elimination.', 7.4, 85, '2000-05-31'),
    ('Glee', 'https://sflix.to/watch-tv/free-glee-hd-39241.4920490', 'A high school glee club navigates relationships, competitions, and life in general.', 6.8, 70, '2009-05-19'),
    ('Full House', 'https://sflix.to/watch-tv/free-full-house-hd-38997.5397439', 'A widowed father enlists the help of his brother-in-law and best friend to raise his three daughters.', 6.7, 72, '1987-09-22'),
    ('Euphoria', 'https://sflix.to/watch-tv/free-euphoria-hd-28898.4793545', 'A group of high school students navigate drugs, relationships, and personal identity.', 8.3, 86, '2019-06-16'),
    ('The Boys', 'https://sflix.to/watch-tv/free-the-boys-hd-33895.5346901', 'A group of vigilantes sets out to expose corrupt superheroes.', 8.7, 91, '2019-07-26'),
    ('Band of Brothers', 'https://sflix.to/watch-tv/free-band-of-brothers-hd-39076.4947757', 'The story of Easy Company of the US Army during World War II.', 9.4, 97, '2001-09-09'),
    ('Chernobyl', 'https://sflix.to/watch-tv/free-chernobyl-hd-42212.5314165', 'The story of the Chernobyl nuclear disaster and the people who made sacrifices to save Europe.', 9.4, 96, '2019-05-06'),
    ('Friday Night Lights', 'https://sflix.to/watch-tv/free-friday-night-lights-hd-38586.5015656', 'The lives of high school football players, coaches, and their families in a small Texas town.', 8.7, 94, '2006-10-03'),
    ('True Detective', 'https://sflix.to/watch-tv/free-true-detective-hd-39487.4866403', 'Seasonal anthology series about crime and justice in different parts of the US.', 9.0, 79, '2014-01-12'),
    ('Planet Earth', 'https://sflix.to/watch-tv/free-planet-earth-hd-38933.4972069', 'A breathtaking look at the natural world and its wonders.', 9.4, 100, '2006-03-05');

-- MOVIE GENRES
INSERT INTO `movie_genres` (`movie_id`, `genre_id`) VALUES
    (1, 1), (1, 5), (1, 17),
    (2, 1), (2, 2), (2, 4),
    (3, 2), (3, 6), (3, 11),
    (4, 2), (4, 9), (4, 13),
    (5, 3), (5, 12), (5, 9),
    (6, 3), (6, 4), (6, 9),
    (7, 4), (7, 13),
    (8, 4), (8, 5),
    (9, 5), (9, 4), (9, 24),
    (10, 5), (10, 4), (10, 17),
    (11, 6), (11, 11), (11, 4),
    (12, 6), (12, 2), (12, 9),
    (13, 7), (13, 2), (13, 11),
    (14, 7), (14, 2), (14, 4),
    (15, 8), (15, 1), (15, 16),
    (16, 8), (16, 10), (16, 16),
    (17, 9), (17, 13), (17, 12),
    (18, 9), (18, 4), (18, 12),
    (19, 10), (19, 11), (19, 4),
    (20, 10), (20, 16), (20, 11),
    (21, 11), (21, 2), (21, 10),
    (22, 11), (22, 4), (22, 7),
    (23, 12), (23, 4), (23, 13),
    (24, 12), (24, 9), (24, 4),
    (25, 23),
    (26, 21), (26, 23),
    (27, 15), (27, 5), (27, 4),
    (28, 15), (28, 4), (28, 16),
    (29, 16), (29, 8), (29, 11),
    (30, 16), (30, 8), (30, 11),
    (31, 17), (31, 1), (31, 4),
    (32, 17), (32, 1), (32, 5),
    (33, 18), (33, 2), (33, 13),
    (34, 18), (34, 2), (34, 6),
    (35, 19), (35, 2), (35, 4),
    (36, 19), (36, 4), (36, 24),
    (37, 20), (37, 4),
    (38, 20), (38, 25), (38, 4),
    (39, 21), (39, 4), (39, 20),
    (40, 21), (40, 4), (40, 20),
    (41, 22), (41, 12), (41, 9),
    (42, 22), (42, 13), (42, 9),
    (43, 23), (43, 24), (43, 13),
    (44, 23), (44, 16), (44, 25);

-- SERIES GENRES
INSERT INTO `series_genres` (`series_id`, `genre_id`) VALUES
    (1, 1), (1, 16),
    (2, 2), (2, 4), (2, 13),
    (3, 6), (3, 3), (3, 12),
    (4, 4), (4, 24), (4, 20),
    (5, 5), (5, 24), (5, 10),
    (6, 6), (6, 11), (6, 2),
    (7, 7), (7, 4), (7, 16),
    (8, 8), (8, 11), (8, 16),
    (9, 9), (9, 12), (9, 13), 
    (10, 10), (10, 2), (10, 4),
    (11, 11), (11, 4), (11, 2),
    (12, 12), (12, 4), (12, 9),
    (13, 13), (13, 4), (13, 1),
    (14, 14), (14, 16), (14, 11),
    (15, 15), (15, 16), (15, 5),
    (16, 16), (16, 4), (16, 1),
    (17, 17), (17, 4), (17, 5),
    (18, 18), (18, 2), (18, 6),
    (19, 19), (19, 24), (19, 20),
    (20, 23), (20, 4), (20, 24),
    (21, 21), (21, 4), (21, 17),
    (22, 4), (22, 12), (22, 13),
    (23, 23), (23, 25), (23, 16);

-- ACTORS
INSERT INTO `actors` (`actor_name`) VALUES 
    ('Jonah Hill'),              -- 1
    ('Michael Cera'),            -- 2
    ('Bradley Cooper'),          -- 3
    ('Zach Galifianakis'),       -- 4
    ('Tom Hardy'),               -- 5
    ('Charlize Theron'),         -- 6
    ('Keanu Reeves'),            -- 7
    ('Michael Nyqvist'),         -- 8
    ('Daniel Kaluuya'),          -- 9
    ('Allison Williams'),        -- 10
    ('Emily Blunt'),             -- 11
    ('John Krasinski'),          -- 12
    ('Tim Robbins'),             -- 13
    ('Morgan Freeman'),          -- 14
    ('Tom Hanks'),               -- 15
    ('Robin Wright'),            -- 16
    ('Keira Knightley'),         -- 17
    ('Matthew Macfadyen'),       -- 18
    ('Ryan Gosling'),            -- 19
    ('Rachel McAdams'),          -- 20
    ('Matthew McConaughey'),     -- 21
    ('Anne Hathaway'),           -- 22
    ('Harrison Ford'),           -- 23
    ('Clint Eastwood'),          -- 24
    ('Eli Wallach'),             -- 25
    ('Jamie Foxx'),              -- 26
    ('Christoph Waltz'),         -- 27
    ('Tim Allen'),               -- 28
    ('Ruthie Henshall'),         -- 29
    ('Mitsuki Saiga'),           -- 30
    ('Brad Pitt'),               -- 31
    ('Ben Affleck'),             -- 32
    ('Rosamund Pike'),           -- 33
    ('Elijah Wood'),             -- 34
    ('Ian McKellen'),            -- 35
    ('Daniel Radcliffe'),        -- 36
    ('Emma Watson'),             -- 37
    ('Karen Allen'),             -- 38
    ('Leonardo DiCaprio'),       -- 39
    ('Rooney Mara'),             -- 40
    ('Daniel Craig'),            -- 41
    ('Mark Ruffalo'),            -- 42
    ('Jack Ma'),                 -- 43
    ('Chai Jing'),               -- 44
    ('Alex Honnold'),            -- 45
    ('Jimmy Chin'),              -- 46
    ('Emma Stone'),              -- 47
    ('Hugh Jackman'),            -- 48
    ('Zac Efron'),               -- 49
    ('Matthew Broderick'),       -- 50
    ('James Earl Jones'),        -- 51
    ('Idina Menzel'),            -- 52
    ('Kristen Bell'),            -- 53
    ('Emilio Estevez'),          -- 54
    ('Molly Ringwald'),          -- 55
    ('Alicia Silverstone'),      -- 56
    ('Stacey Dash'),             -- 57
    ('Christian Bale'),          -- 58
    ('Heath Ledger'),            -- 59
    ('Robert Downey Jr.'),       -- 60
    ('Chris Evans'),             -- 61
    ('Matt Damon'),              -- 62
    ('George MacKay'),           -- 63
    ('Dean-Charles Chapman'),    -- 64
    ('Jesse Eisenberg'),         -- 65
    ('Andrew Garfield'),         -- 66
    ('Rami Malek'),              -- 67
    ('Lucy Boynton'),            -- 68
    ('Sylvester Stallone'),      -- 69
    ('Talia Shire'),             -- 70
    ('Denzel Washington'),       -- 71
    ('Will Patton'),             -- 72
    ('Jack Nicholson'),          -- 73
    ('Faye Dunaway'),            -- 74
    ('Fred MacMurray'),          -- 75
    ('Barbara Stanwyck'),        -- 76
    ('Michelle Alexander'),      -- 77
    ('Bryan Stevenson'),         -- 78
    ('Pierce Brosnan'),          -- 79
    ('Steve Carell'),            -- 80
    ('Rainn Wilson'),            -- 81
    ('Bryan Cranston'),          -- 82
    ('Aaron Paul'),              -- 83
    ('Winona Ryder'),            -- 84
    ('David Harbour'),           -- 85
    ('Claire Foy'),              -- 86
    ('Matt Smith'),              -- 87
    ('Caitriona Balfe'),         -- 88
    ('Sam Heughan'),             -- 89
    ('Pedro Pascal'),            -- 90
    ('Gina Carano'),             -- 91
    ('Kevin Costner'),           -- 92
    ('Luke Grimes'),             -- 93
    ('Zach Tyler Eisen'),        -- 94
    ('Mae Whitman'),             -- 95
    ('Jonathan Groff'),          -- 96
    ('Holt McCallany'),          -- 97
    ('Emilia Clarke'),           -- 98
    ('Kit Harington'),           -- 99
    ('Matthew Fox'),             -- 100
    ('Evangeline Lilly'),        -- 101
    ('Kyle MacLachlan'),         -- 102
    ('Sheryl Lee'),              -- 103
    ('James Gandolfini'),        -- 104
    ('Lorraine Bracco'),         -- 105
    ('Jeff Probst'),             -- 106
    ('Lea Michele'),             -- 107
    ('Matthew Morrison'),        -- 108
    ('Bob Saget'),               -- 109
    ('John Stamos'),             -- 110
    ('Zendaya'),                 -- 111
    ('Hunter Schafer'),          -- 112
    ('Karl Urban'),              -- 113
    ('Jack Quaid'),              -- 114
    ('Damian Lewis'),            -- 115
    ('Ron Livingston'),          -- 116
    ('Jared Harris'),            -- 117
    ('Stellan Skarsgård'),       -- 118
    ('Kyle Chandler'),           -- 119
    ('Connie Britton'),          -- 120
    ('Woody Harrelson'),         -- 121
    ('David Attenborough'),      -- 122
    ('Sigourney Weaver');        -- 123

-- ACTED IN MOVIES
INSERT INTO `acted_in_movies` (`movie_actor_id`, `movie_id`, `role`)
VALUES
    (1, 1, 'Seth'),
    (2, 1, 'Evan'),
    (3, 2, 'Phil Wenneck'),
    (4, 2, 'Alan Garner'),
    (5, 3, 'Max Rockatansky'),
    (6, 3, 'Imperator Furiosa'),
    (7, 4, 'John Wick'),
    (8, 4, 'Viggo Tarasov'),
    (9, 5, 'Chris Washington'),
    (10, 5, 'Rose Armitage'),
    (11, 6, 'Evelyn Abbott'),
    (12, 6, 'Lee Abbott'),
    (13, 7, 'Andy Dufresne'),
    (14, 7, 'Ellis Boyd "Red" Redding'),
    (15, 8, 'Forrest Gump'),
    (16, 8, 'Jenny Curran'),
    (17, 9, 'Elizabeth Bennet'),
    (18, 9, 'Mr. Darcy'),
    (19, 10, 'Noah Calhoun'),
    (20, 10, 'Allie Hamilton'),
    (21, 11, 'Cooper'),
    (22, 11, 'Brand'),
    (19, 12, 'K'),
    (23, 12, 'Rick Deckard'),
    (24, 13, 'Blondie'),
    (25, 13, 'Tuco'),
    (26, 14, 'Django Freeman'),
    (27, 14, 'Dr. King Schultz'),
    (15, 15, 'Woody'),
    (28, 15, 'Buzz Lightyear'),
    (29, 16, 'Chihiro Ogino'),
    (30, 16, 'Haku'),
    (14, 17, 'Detective Somerset'),
    (31, 17, 'Detective Mills'),
    (32, 18, 'Nick Dunne'),
    (33, 18, 'Amy Dunne'),
    (34, 19, 'Frodo Baggins'),
    (35, 19, 'Gandalf'),
    (36, 20, 'Harry Potter'),
    (37, 20, 'Hermione Granger'),
    (23, 21, 'Indiana Jones'),
    (38, 21, 'Marion Ravenwood'),
    (5, 22, 'John Fitzgerald'),
    (39, 22, 'Hugh Glass'),
    (40, 23, 'Lisbeth Salander'),
    (41, 23, 'Mikael Blomkvist'),
    (39, 24, 'Teddy Daniels'),
    (42, 24, 'Chuck Aule'),
    (43, 25, 'Himself'),
    (44, 25, 'Herself'),
    (45, 26, 'Himself'),
    (46, 26, 'Himself'),
    (47, 27, 'Mia Dolan'),
    (19, 27, 'Sebastian Wilder'),
    (48, 28, 'P.T. Barnum'),
    (49, 28, 'Phillip Carlyle'),
    (50, 29, 'Simba'),
    (51, 29, 'Mufasa'),
    (52, 30, 'Elsa'),
    (53, 30, 'Anna'),
    (54, 31, 'Andrew Clark'),
    (55, 31, 'Claire Standish'),
    (56, 32, 'Cher Horowitz'),
    (57, 32, 'Dionne Davenport'),
    (58, 33, 'Bruce Wayne'),
    (59, 33, 'Joker'),
    (60, 34, 'Tony Stark'),
    (61, 34, 'Steve Rogers'),
    (15, 35, 'Captain Miller'),
    (62, 35, 'Private Ryan'),
    (63, 36, 'Lance Corporal Schofield'),
    (64, 36, 'Lance Corporal Blake'),
    (65, 37, 'Mark Zuckerberg'),
    (66, 37, 'Eduardo Saverin'),
    (67, 38, 'Freddie Mercury'),
    (68, 38, 'Mary Austin'),
    (69, 39, 'Rocky Balboa'),
    (70, 39, 'Adrian Pennino'),
    (71, 40, 'Coach Herman Boone'),
    (72, 40, 'Coach Bill Yoast'),
    (73, 41, 'J.J. Gittes'),
    (74, 41, 'Evelyn Mulwray'),
    (75, 42, 'Walter Neff'),
    (76, 42, 'Phyllis Dietrichson'),
    (77, 43, 'Herself'),
    (78, 43, 'Himself'),
    (14, 44, 'Narrator'),
    (79, 44, 'Narrator');

-- ACTED IN SERIES
INSERT INTO `acted_in_series` (`series_actor_id`, `series_id`, `role`)
VALUES
    (80, 1, 'Michael Scott'),
    (81, 1, 'Dwight Schrute'),
    (82, 2, 'Walter White'),
    (83, 2, 'Jesse Pinkman'),
    (84, 3, 'Joyce Byers'),
    (85, 3, 'Jim Hopper'),
    (86, 4, 'Queen Elizabeth II'),
    (87, 4, 'Prince Philip'),
    (88, 5, 'Claire Randall'),
    (89, 5, 'Jamie Fraser'),
    (90, 6, 'Din Djarin'),
    (91, 6, 'Cara Dune'),
    (92, 7, 'John Dutton'),
    (93, 7, 'Kayce Dutton'),
    (94, 8, 'Aang'),
    (95, 8, 'Katara'),
    (96, 9, 'Holden Ford'),
    (97, 9, 'Bill Tench'),
    (98, 10, 'Daenerys Targaryen'),
    (99, 10, 'Jon Snow'),
    (100, 11, 'Jack Shepherd'),
    (101, 11, 'Kate Austen'),
    (102, 12, 'Dale Cooper'),
    (103, 12, 'Laura Palmer'),
    (104, 13, 'Tony Soprano'),
    (105, 13, 'Dr. Jennifer Melfi'),
    (106, 14, 'Host'),
    (107, 15, 'Rachel Berry'),
    (108, 15, 'Will Schuester'),
    (109, 16, 'Danny Tanner'),
    (110, 16, 'Jesse Katsopolis'),
    (111, 17, 'Rue Bennett'),
    (112, 17, 'Jules Vaughn'),
    (113, 18, 'Billy Butcher'),
    (114, 18, 'Hughie Campbell'),
    (115, 19, 'Major Richard Winters'),
    (116, 19, 'Captain Lewis Nixon'),
    (117, 20, 'Valery Legasov'),
    (118, 20, 'Boris Shcherbina'),
    (119, 21, 'Eric Taylor'),
    (120, 21, 'Tami Taylor'),
    (21, 22, 'Rust Cohle'),
    (121, 22, 'Marty Hart'),
    (122, 23, 'Narrator'),
    (123, 23, 'Narrator');

-- MOVIE AWARDS (Randomly Generated)
INSERT INTO `movie_awards` (`movie_id`, `award_org`, `award_title`, `award_status`) VALUES
    (1, 'Academy Awards', 'Best Picture', 'Won'),
    (2, 'Golden Globe Awards', 'Best Actor', 'Nominated'),
    (3, 'BAFTA Awards', 'Best Director', 'Won'),
    (4, 'Cannes Film Festival', 'Best Film', 'Nominated'),
    (5, 'Golden Globe Awards', 'Best Screenplay', 'Won'),
    (6, 'Academy Awards', 'Best Actress', 'Won'),
    (7, 'Sundance Film Festival', 'Best Film Feature', 'Nominated'),
    (8, 'Golden Globe Awards', 'Best Foreign Film', 'Won'),
    (9, 'Critics Choice Awards', 'Best Ensemble Cast', 'Nominated'),
    (10, 'César Awards', 'Best Director', 'Won'),
    (11, 'Golden Globe Awards', 'Best Original Song', 'Won'),
    (12, 'Academy Awards', 'Best Adapted Screenplay', 'Nominated'),
    (13, 'Sundance Film Festival', 'Audience Award', 'Won'),
    (14, 'Academy Awards', 'Best Supporting Actor', 'Won'),
    (15, 'Golden Globe Awards', 'Best Motion Picture', 'Nominated'),
    (16, 'BAFTA Awards', 'Best Actor in a Leading Role', 'Won'),
    (17, 'Cannes Film Festival', 'Best Actor', 'Nominated'),
    (18, 'Academy Awards', 'Best Cinematography', 'Won'),
    (19, 'Golden Globe Awards', 'Best Actress', 'Nominated'),
    (20, 'Critics Choice Awards', 'Best Picture', 'Won'),
    (21, 'Academy Awards', 'Best Original Screenplay', 'Won'),
    (22, 'Golden Globe Awards', 'Best Director', 'Nominated'),
    (23, 'BAFTA Awards', 'Best Costume Design', 'Won'),
    (24, 'César Awards', 'Best Film', 'Nominated'),
    (25, 'Golden Globe Awards', 'Best Actor in a Film', 'Won'),
    (26, 'Critics Choice Awards', 'Best Visual Effects', 'Nominated'),
    (27, 'Academy Awards', 'Best Film Editing', 'Won'),
    (28, 'Golden Globe Awards', 'Best Actor in a Musical', 'Nominated'),
    (29, 'Cannes Film Festival', 'Best Screenplay', 'Won'),
    (30, 'Sundance Film Festival', 'Best Feature Film', 'Nominated'),
    (31, 'Golden Globe Awards', 'Best Performance by an Ensemble', 'Won'),
    (32, 'Academy Awards', 'Best Supporting Actress', 'Nominated'),
    (33, 'Golden Globe Awards', 'Best Series', 'Won'),
    (34, 'BAFTA Awards', 'Best Production Design', 'Won'),
    (35, 'César Awards', 'Best Director', 'Nominated'),
    (36, 'Academy Awards', 'Best Makeup', 'Won'),
    (37, 'Golden Globe Awards', 'Best Performance by an Actor', 'Nominated'),
    (38, 'Critics Choice Awards', 'Best Cast Ensemble', 'Won'),
    (39, 'Cannes Film Festival', 'Best Actor in a Supporting Role', 'Nominated'),
    (40, 'Golden Globe Awards', 'Best Motion Picture', 'Won'),
    (41, 'Academy Awards', 'Best Supporting Actor', 'Nominated'),
    (42, 'BAFTA Awards', 'Best Editing', 'Won'),
    (43, 'Golden Globe Awards', 'Best Motion Picture', 'Nominated'),
    (44, 'Sundance Film Festival', 'Best Film Feature', 'Won');

-- SERIES AWARDS (Randomly Generated)
INSERT INTO `series_awards` (`series_id`, `award_org`, `award_title`, `award_status`) VALUES
    (1, 'Golden Globe Awards', 'Best Ensemble Performance', 'Nominated'),
    (2, 'Primetime Emmy Awards', 'Outstanding Direction', 'Won'),
    (3, 'Screen Actors Guild Awards', 'Outstanding Achievement in Acting', 'Nominated'),
    (4, 'BAFTA Television Awards', 'Best Editing', 'Won'),
    (5, 'Golden Globe Awards', 'Best Performance by an Actor', 'Nominated'),
    (6, 'Primetime Emmy Awards', 'Outstanding Cinematography', 'Won'),
    (7, 'Golden Globe Awards', 'Best Performance by an Actress', 'Nominated'),
    (8, 'Saturn Awards', 'Best Visual Effects in Television', 'Won'),
    (9, 'Critics Choice Awards', 'Best Performance by an Actor', 'Nominated'),
    (10, 'Golden Globe Awards', 'Best Performance by a Lead Actress', 'Nominated'),
    (11, 'Primetime Emmy Awards', 'Best Writing', 'Won'),
    (12, 'Golden Globe Awards', 'Best Original Score', 'Won'),
    (13, 'Critics Choice Awards', 'Best Performance in a Leading Role', 'Won'),
    (14, 'Golden Globe Awards', 'Best Performance by an Actor', 'Nominated'),
    (15, 'Golden Globe Awards', 'Best Song in Television', 'Won'),
    (16, 'Primetime Emmy Awards', 'Best Production Design', 'Nominated'),
    (17, 'Golden Globe Awards', 'Best Performance in a Supporting Role', 'Won'),
    (18, 'Golden Globe Awards', 'Best Performance by an Actor', 'Nominated'),
    (19, 'Screen Actors Guild Awards', 'Outstanding Cast Performance', 'Won'),
    (20, 'Critics Choice Awards', 'Best Performance by a Lead Actor', 'Nominated'),
    (21, 'Primetime Emmy Awards', 'Best Editing', 'Won'),
    (22, 'Golden Globe Awards', 'Best Performance in a Supporting Role', 'Nominated'),
    (23, 'Primetime Emmy Awards', 'Best Cinematography', 'Won');

-- ADMIN PICKS
INSERT INTO `admin_picks` (`admin_pick_type`, `movie_id`, `series_id`, `admin_account_id`, `date_added`, `admin_pick_review`)
VALUES
('movie', 7, NULL, 1, '2002-12-03', 'A cinematic masterpiece that has stood the test of time since its release in 1994. Directed by Frank Darabont and based on Stephen Kings novella, this film is a testament to the power of storytelling and the indomitable human spirit.'),
('series', NULL, 2, 2, '2010-05-28', 'Not just a TV show; its a work of art. With its brilliant performances, gripping plot, and thought-provoking themes, its a series that will continue to captivate audiences for years to come.'),
('movie', 14, NULL, 3, '2013-08-19', 'Tarantinos fiercely grossing action flick on blaxploitation is a cheer inviting cake of blood highlighting the age old conflict ridden relationship between the 2 sides of a game of chess - the Whites and Blacks, as they are called.'),
('series', NULL, 10, 4, '2021-10-30', 'The storyline is full of unpredictable surprises which leave you scratching your head. Every season has its own set of twists and turns which keeps the audience entertained, thrilled and glued to the screen till the last episode.');

-- MOVIE REVIEWS (Randomly Generated)
INSERT INTO `movie_reviews` (`user_profile_id`, `movie_id`, `review_text`, `rating`, `date_added`, `time_added`)
VALUES
(1, 1, 'I really enjoyed this one! The story was captivating, and the performances were outstanding.', 8.5, '2024-11-10', '15:32:11'),
(3, 1, 'This one didnt do it for me. The story felt weak, and the performances werent strong enough to carry it.', 4.5, '2024-11-18', '20:14:22'),
(5, 2, 'What a thrill! The plot kept me on the edge of my seat, and the twists were totally unexpected.', 8.7, '2024-11-14', '19:45:09'),
(2, 2, 'Honestly, this was a pretty forgettable watch. The plot was predictable, and I didnt feel engaged.', 5.6, '2024-11-20', '13:02:33'),
(4, 3, 'Such an emotional film! The story was beautifully told, and it really stuck with me.', 9.3, '2024-11-09', '17:27:45'),
(8, 3, 'A heartwarming movie that made me smile. The characters felt real and relatable.', 8.2, '2024-11-22', '10:15:57'),
(6, 4, 'An absolute blast to watch! It had nonstop action and top-tier direction. Loved every minute.', 8.9, '2024-11-11', '12:11:11'),
(1, 4, 'Visually stunning, but the substance just wasnt there for me. Felt a bit hollow.', 2.3, '2024-11-13', '21:09:16'),
(3, 5, 'I couldnt stop thinking about this one. It was such a gripping story about resilience and hope.', 8.4, '2024-11-08', '16:22:30'),
(7, 5, 'Loved this one! A fresh take on a classic genre, with some really powerful storytelling.', 9.2, '2024-11-21', '09:33:49'),
(2, 6, 'Such a fun movie! It kept me entertained from start to finish, a great watch for a light evening.', 7.8, '2024-11-12', '14:44:05'),
(5, 6, 'This one really hit the sweet spot. A perfect mix of humor and drama that felt just right.', 8.6, '2024-11-19', '22:51:23'),
(8, 7, 'Honestly, I wasnt impressed. The story didnt have much heart, and it felt kind of flat to me.', 5.4, '2024-11-16', '11:26:34'),
(4, 7, 'This movie was unforgettable! The visuals alone were worth the watch, but the story was just as stunning.', 8.8, '2024-11-20', '08:14:55'),
(6, 8, 'Such a beautiful film. The performances were incredible, and the story really moved me.', 9.1, '2024-11-10', '10:45:19'),
(7, 8, 'Totally lived up to the hype. A spectacular movie with great energy and emotion!', 8.3, '2024-11-17', '18:23:07'),
(1, 9, 'Honestly, I was expecting more. It just didnt deliver, and the characters felt underwhelming.', 4.5, '2024-11-14', '20:12:44'),
(3, 9, 'This film was absolutely exhilarating! It kept me on the edge of my seat the entire time.', 9.0, '2024-11-18', '19:37:59'),
(5, 10, 'Wow, what a ride! This film pulled me in and never let go. Absolutely brilliant.', 9.2, '2024-11-09', '11:30:14'),
(2, 10, 'I was blown away by the visuals. A powerful message wrapped in breathtaking cinematography.', 8.5, '2024-11-22', '07:40:18'),
(4, 11, 'Honestly, this movie didnt offer anything new. It felt like a rehash of what weve seen before.', 2.3, '2024-11-11', '15:11:31'),
(8, 11, 'This was actually a pretty inspiring film. It had a lot of memorable moments that stayed with me.', 8.2, '2024-11-13', '21:54:06'),
(6, 12, 'Such a fun film! It had a perfect blend of suspense and humor that kept me laughing and on the edge of my seat.', 8.6, '2024-11-08', '12:05:49'),
(7, 12, 'I cant stop thinking about this one. The direction was exceptional, and the narrative was so captivating.', 9.4, '2024-11-21', '17:42:53'),
(1, 13, 'This movie really grabbed me. The story was intense, and I was hooked from start to finish.', 8.8, '2024-11-12', '09:19:47'),
(3, 13, 'To be honest, I found it forgettable. It just didnt have the spark I was hoping for.', 3.7, '2024-11-19', '16:48:38'),
(2, 14, 'This was such a magical experience. The story left a lasting impression, and I loved every minute.', 9.2, '2024-11-16', '20:26:01'),
(5, 14, 'I was so moved by this one. Its a visual feast, and the emotional payoff was worth it.', 8.3, '2024-11-20', '08:57:24'),
(8, 15, 'This was a game-changer. The message was powerful, and the film itself was groundbreaking.', 9.5, '2024-11-10', '10:11:42'),
(4, 15, 'I was let down by this one. The concept had so much potential, but the execution didnt live up to it.', 5.2, '2024-11-17', '22:14:53'),
(6, 16, 'I loved how unpredictable this one was. It had me guessing the entire time, which is always a win.', 8.7, '2024-11-14', '21:02:11'),
(7, 16, 'This has to be one of Studio Ghiblis best films ever, its going to stay with me for a while', 9.2, '2024-11-18', '13:23:16'),
(1, 17, 'I was so moved by this one. The plot was gripping, and the emotional weight really hit home.', 9.0, '2024-11-09', '10:37:49'),
(3, 17, 'I really liked this film. It was bold and unique, and I appreciated the fresh perspective.', 8.6, '2024-11-22', '18:29:41'),
(5, 18, 'Such a beautiful journey. It was emotionally powerful and really made an impact on me.', 9.3, '2024-11-11', '15:48:30'),
(2, 18, 'This one didnt quite work for me. I felt like it had so much potential but didnt quite deliver.', 6.0, '2024-11-13', '11:12:56'),
(4, 19, 'What a beautiful film! The visuals were amazing, and the story had so much heart.', 9.1, '2024-11-08', '19:03:21'),
(8, 19, 'This movie had a bold narrative and characters that will stay with me for a long time.', 8.4, '2024-11-21', '09:44:28'),
(6, 20, 'Absolutely stunning! The visuals were breathtaking, and the movie had a really solid story.', 9.2, '2024-11-12', '16:22:37'),
(7, 20, 'This movie was incredible. The story was extraordinary, and the direction was top-notch.', 8.8, '2024-11-19', '18:56:04'),
(1, 21, 'Such a beautiful movie. It felt timeless, and the story really resonated with me.', 8.9, '2024-11-10', '13:11:27'),
(3, 21, 'This one didnt live up to my expectations. It lacked charm and didnt leave an impact.', 4.9, '2024-11-16', '18:52:15'),
(2, 22, 'A visually innovative film that really stands out from the crowd.', 9.0, '2024-11-14', '12:21:08'),
(5, 22, 'A cleverly written story that was brilliantly executed. Really enjoyed this one.', 8.8, '2024-11-17', '21:33:14'),
(4, 23, 'An unforgettable cinematic journey. A must-see for any movie lover.', 9.3, '2024-11-11', '17:25:30'),
(8, 23, 'A masterful portrayal of human emotions. This movie truly touched me.', 8.7, '2024-11-20', '08:16:49'),
(6, 24, 'An awe-inspiring tale with incredible visuals. I couldnt take my eyes off the screen.', 9.2, '2024-11-12', '15:03:47'),
(7, 24, 'Fails to deliver on its ambitious premise. I expected more from this one.', 3.8, '2024-11-19', '19:29:51'),
(1, 25, 'A refreshingly unique story. It kept me hooked from start to finish.', 8.6, '2024-11-08', '13:07:23'),
(3, 25, 'A fascinating exploration of complex themes. Definitely a thought-provoking film.', 9.1, '2024-11-21', '10:24:35'),
(5, 26, 'A spectacular film with phenomenal acting. Totally captivated me.', 9.4, '2024-11-14', '20:31:56'),
(2, 26, 'A powerful and moving story with deep insights. Highly recommend it.', 8.7, '2024-11-18', '14:41:39'),
(8, 27, 'A predictable plot that failed to engage me. Not what I was hoping for.', 4.7, '2024-11-10', '11:15:44'),
(4, 27, 'A visually stunning and emotionally impactful movie. A true emotional rollercoaster.', 8.4, '2024-11-13', '22:02:18'),
(6, 28, 'A breathtaking spectacle of cinematic artistry. I was in awe from beginning to end.', 9.0, '2024-11-09', '12:48:30'),
(7, 28, 'A compelling narrative with an unforgettable climax. This one will stay with me for a while.', 5.3, '2024-11-22', '18:57:09'),
(1, 29, 'One of the most unforgettable classics of our childhoods, there will never be another film like this one', 9.3, '2024-11-12', '09:12:54'),
(3, 29, 'A thoughtful and engaging exploration of humanity. Really made me think about things.', 9.2, '2024-11-19', '16:22:18'),
(5, 30, 'A mesmerizing film with profound meaning. I couldnt stop thinking about it afterwards.', 9.3, '2024-11-16', '20:44:33'),
(2, 30, 'A sluggish pace makes it a chore to watch. Not my cup of tea.', 3.9, '2024-11-20', '10:34:41'),
(4, 31, 'A delightful mix of humor and heartfelt moments. I really enjoyed this one.', 9.1, '2024-11-10', '10:52:27'),
(8, 31, 'A visually breathtaking and deeply resonant film. It stayed with me long after it ended.', 8.5, '2024-11-17', '21:21:39'),
(6, 32, 'A thrilling and suspenseful journey into the unknown. I was on the edge of my seat!', 8.7, '2024-11-14', '22:31:59'),
(7, 32, 'An expertly crafted tale of resilience and courage. The characters were so well developed.', 9.0, '2024-11-18', '11:34:23'),
(1, 33, 'An enchanting story with stunning visuals. A film you cant help but get lost in.', 9.1, '2024-11-09', '14:07:53'),
(3, 33, 'A fresh perspective on familiar themes. Really enjoyed the twist on old ideas.', 8.6, '2024-11-21', '12:43:36'),
(5, 34, 'A gripping narrative that keeps you hooked. I couldnt look away.', 9.4, '2024-11-11', '16:12:15'),
(2, 34, 'Overloaded with clichés and lacks originality. Didnt impress me much.', 6.3, '2024-11-13', '10:19:44'),
(8, 35, 'A deeply emotional and beautifully shot film. It really pulled at my heartstrings.', 9.3, '2024-11-08', '18:22:18'),
(4, 35, 'Uninspired performances and a weak storyline. Not what I expected at all.', 3.5, '2024-11-20', '09:45:27'),
(6, 36, 'An edge-of-your-seat thriller with brilliant pacing. It kept me guessing the whole time.', 9.2, '2024-11-12', '16:51:13'),
(7, 36, 'A bold reimagining of a classic genre. I really liked how they did it differently.', 8.5, '2024-11-19', '18:12:44'),
(1, 37, 'An inspiring tale with breathtaking cinematography. Beautiful in every sense.', 9.0, '2024-11-14', '20:48:22'),
(3, 37, 'A cinematic triumph that transcends expectations. I wasnt ready for how good this was.', 8.8, '2024-11-18', '12:42:11'),
(5, 38, 'A profound story told with exceptional artistry. This one left a real impact on me.', 9.4, '2024-11-09', '13:58:37'),
(2, 38, 'An incoherent script with no clear direction. Really struggled to follow this one.', 4.5, '2024-11-22', '19:11:20'),
(4, 39, 'A magical journey with stunning visuals and depth. I was so immersed in this film.', 9.1, '2024-11-10', '11:32:49'),
(8, 39, 'A groundbreaking film that pushes the boundaries. This one definitely stands out.', 8.9, '2024-11-16', '20:41:36'),
(6, 40, 'Disappointing special effects for a modern film. It took me out of the experience.', 5.5, '2024-11-14', '21:09:22'),
(7, 40, 'A magnificent tale told with exceptional skill. Truly a remarkable movie.', 8.5, '2024-11-18', '13:24:51'),
(1, 41, 'A heartwarming and thought-provoking film. Really made me reflect on life.', 9.2, '2024-11-08', '10:14:32'),
(3, 41, 'Fails to deliver any memorable moments. I wont be thinking about this one much.', 3.2, '2024-11-21', '10:47:19'),
(5, 42, 'A thrilling masterpiece with unforgettable performances. I was hooked from start to finish.', 9.5, '2024-11-12', '14:33:54'),
(2, 42, 'A visually stunning and deeply moving experience. A film that really stuck with me.', 8.9, '2024-11-19', '16:41:23'),
(4, 43, 'An epic tale with incredible depth and meaning. This is one for the ages.', 9.0, '2024-11-16', '21:38:31'),
(8, 43, 'A spectacular film that inspires and amazes. I loved every minute of it.', 8.6, '2024-11-20', '08:59:13'),
(6, 44, 'A thought-provoking story with a powerful message. Really made me think about the world.', 9.3, '2024-11-10', '10:41:12'),
(7, 44, 'A phenomenal journey through an unforgettable narrative. A truly incredible film.', 8.7, '2024-11-17', '20:15:44');

-- SERIES REVIEWS (Randomly Generated)
INSERT INTO `series_reviews` (`user_profile_id`, `series_id`, `review_text`, `rating`, `date_added`, `time_added`)
VALUES
(1, 1, 'I loved this series! The characters were so well-developed, and the story really pulled me in.', 8.5, '2024-09-15', '14:23:45'),
(2, 1, 'The visuals were stunning, and the plot kept me hooked. Definitely a great watch!', 9.0, '2024-10-12', '10:35:12'),
(3, 2, 'The runtime felt way too long, and the story dragged for me.', 4.3, '2024-09-20', '16:40:22'),
(5, 2, 'I really enjoyed this series. The performances were great, and it had a fresh perspective.', 8.3, '2024-08-18', '18:10:50'),
(6, 3, 'Such a fun mix of excitement and emotion. I really had a great time watching this.', 9.2, '2024-09-11', '12:25:14'),
(4, 3, 'The storytelling was fantastic, and there were some amazing twists that kept me hooked.', 8.9, '2024-07-22', '15:00:01'),
(7, 4, 'The characters didnt really connect with me, and the emotional depth was lacking.', 4.6, '2024-08-05', '11:15:30'),
(8, 4, 'I loved the character arcs and the meaningful dialogue. It was a great series overall.', 9.0, '2024-07-15', '09:45:00'),
(1, 5, 'The story was really well-crafted, and the performances were strong. Definitely worth watching.', 8.6, '2024-10-03', '14:35:50'),
(3, 5, 'I was drawn into the atmosphere. The themes were intriguing, and it had such a compelling vibe.', 9.1, '2024-06-18', '17:50:10'),
(2, 6, 'Such a brilliant concept, and they executed it flawlessly. A real standout.', 8.9, '2024-08-22', '12:20:22'),
(6, 6, 'The storytelling was unique, and there were so many memorable moments. Definitely enjoyed this one.', 8.8, '2024-07-28', '10:05:45'),
(5, 7, 'What a rollercoaster! This series kept me on the edge of my seat the entire time.', 9.3, '2024-09-05', '15:45:12'),
(8, 7, 'I didnt feel the performances here. The entire cast didnt seem convincing to me.', 6.1, '2024-10-19', '18:30:00'),
(4, 8, 'A truly innovative series that really got me thinking. Loved the unique approach!', 8.8, '2024-08-09', '13:15:45'),
(7, 8, 'The direction was beautiful, and there were so many heartwarming moments. Definitely a must-watch!', 9.0, '2024-07-19', '16:22:00'),
(3, 9, 'I just couldnt get into this one. The narrative felt disconnected, and I didnt find it captivating.', 3.2, '2024-09-01', '11:45:22'),
(1, 9, 'This series really resonated with me. The pacing was perfect, and it had such emotional depth.', 9.1, '2024-08-11', '10:50:11'),
(2, 10, 'Such a thought-provoking series. It was done incredibly well, and the ideas stayed with me.', 8.6, '2024-07-23', '14:32:09'),
(6, 10, 'This one kept me on the edge of my seat! The twists and turns were so clever.', 8.9, '2024-08-12', '16:40:33'),
(8, 11, 'This one didnt work for me. The concept felt unoriginal, and it didnt stand out in any way.', 6.2, '2024-10-02', '14:55:29'),
(5, 11, 'The performances were amazing, and the story kept me hooked. I couldnt stop watching.', 9.2, '2024-09-13', '12:45:18'),
(4, 12, 'This series was unforgettable! So many twists and surprises, I didnt expect any of it.', 9.0, '2024-07-30', '11:22:00'),
(7, 12, 'Such a creative and bold approach to storytelling. Really loved this one.', 8.8, '2024-09-21', '13:20:45'),
(2, 13, 'I didnt enjoy this one. The melodrama was way too much, and there wasnt much substance.', 4.8, '2024-08-16', '10:15:35'),
(3, 13, 'Such an ambitious series! The emotional payoff was totally worth it.', 8.5, '2024-09-26', '15:40:20'),
(1, 14, 'This series was so thrilling, and the performances were top-notch. Definitely one of my favorites.', 9.2, '2024-07-25', '09:50:55'),
(6, 14, 'I was a bit disappointed by the cinematography. The visuals didnt quite live up to expectations.', 3.2, '2024-08-27', '14:05:12'),
(7, 15, 'I was totally entertained by this one! The story had me hooked from beginning to end.', 9.0, '2024-09-09', '17:15:30'),
(4, 15, 'Such a rare gem. The story really left a lasting impression on me.', 8.7, '2024-10-10', '16:50:22'),
(5, 16, 'The humor didnt land for me. It was kind of flat and didnt do much for the story.', 5.6, '2024-07-17', '12:10:45'),
(8, 16, 'This series was emotionally charged and the visuals were breathtaking. I really enjoyed it.', 8.9, '2024-09-15', '10:55:33'),
(3, 17, 'This one had me on the edge of my seat! A thrilling ride from start to finish.', 9.0, '2024-08-29', '13:35:11'),
(1, 17, 'Such a unique vision! The series was so well done, and the excellence really showed through.', 8.8, '2024-09-18', '12:25:45'),
(6, 18, 'The narrative was bold and really grabbed my attention. It kept me captivated the whole time.', 8.6, '2024-07-22', '14:30:12'),
(2, 18, 'There were too many plot holes for me to overlook. It just didnt quite come together.', 2.2, '2024-08-03', '15:55:25'),
(7, 19, 'This series had such powerful storytelling. The moments were unforgettable and really stuck with me.', 9.1, '2024-10-05', '17:20:40'),
(4, 19, 'Such a deeply moving series. The execution was fantastic, and it hit me emotionally.', 8.9, '2024-09-12', '10:10:20'),
(8, 20, 'The cinematography was incredible! This series was epic in every sense of the word.', 8.7, '2024-08-31', '11:45:10'),
(5, 20, 'The acting was superb, and the storyline really made me think. I loved it.', 9.0, '2024-07-18', '16:50:50'),
(2, 21, 'This series didnt do it for me. It was pretty forgettable from start to finish.', 4.0, '2024-09-25', '15:35:40'),
(3, 21, 'I really liked this one. It was captivating and innovative, definitely worth the watch!', 8.6, '2024-07-21', '13:50:15'),
(6, 22, 'This series was such a thrilling ride! The unexpected twists really kept me on my toes.', 9.2, '2024-08-10', '17:45:30'),
(7, 22, 'This series sets a new standard in storytelling. So impressive and exciting to watch.', 8.8, '2024-09-14', '12:55:50'),
(1, 23, 'This story really kept me hooked! I was on the edge of my seat the whole time.', 9.0, '2024-07-29', '14:20:15'),
(4, 23, 'Such a beautiful series! It really pulled at my heartstrings.', 8.7, '2024-08-15', '15:35:00');

-- WATCHLISTS
INSERT INTO `watchlists` (`user_profile_id`, `list_type`)
VALUES
(1, 'movies'),
(1, 'series'),
(2, 'movies'),
(2, 'series'),
(3, 'movies'),
(3, 'series'),
(4, 'movies'),
(4, 'series'),
(5, 'movies'),
(5, 'series'),
(6, 'movies'),
(6, 'series'),
(7, 'movies'),
(7, 'series'),
(8, 'movies'),
(8, 'series');

-- MOVIE WATCHLISTS (Randomly Generated)
INSERT INTO `movie_watchlists` (`movie_watchlist_id`, `movie_id`, `time_added`, `date_added`)
VALUES
(1, 5, '12:23:15', '2024-11-15'),
(3, 18, '16:08:37', '2024-11-12'),
(5, 22, '09:55:45', '2024-11-20'),
(7, 7, '14:14:20', '2024-11-10'),
(9, 19, '08:01:34', '2024-11-17'),
(11, 35, '16:43:25', '2024-11-22'),
(13, 40, '10:12:55', '2024-11-13'),
(15, 12, '20:19:10', '2024-11-18'),
(1, 2, '15:30:47', '2024-11-14'),
(3, 23, '07:12:11', '2024-11-09'),
(5, 36, '12:05:32', '2024-11-19'),
(7, 9, '13:35:29', '2024-11-11'),
(9, 29, '17:10:22', '2024-11-21'),
(11, 17, '21:29:48', '2024-11-16'),
(13, 28, '05:45:53', '2024-11-23'),
(15, 32, '11:07:02', '2024-11-10'),
(1, 43, '22:05:31', '2024-11-12'),
(3, 26, '14:01:19', '2024-11-08'),
(5, 44, '18:15:09', '2024-11-20'),
(7, 11, '06:14:57', '2024-11-17'),
(9, 38, '11:45:33', '2024-11-14'),
(11, 14, '23:59:44', '2024-11-22'),
(13, 8, '16:08:00', '2024-11-18'),
(15, 24, '09:30:03', '2024-11-19'),
(1, 21, '22:45:10', '2024-11-11'),
(3, 16, '07:25:17', '2024-11-15'),
(5, 10, '10:12:50', '2024-11-08'),
(7, 33, '09:18:49', '2024-11-21'),
(9, 20, '03:55:42', '2024-11-13'),
(11, 31, '15:18:27', '2024-11-16');

-- SERIES WATCHLISTS (Randomly Generated)
INSERT INTO `series_watchlists` (`series_watchlist_id`, `series_id`, `time_added`, `date_added`)
VALUES
(2, 3, '14:23:45', '2024-10-21'),
(4, 6, '09:12:33', '2024-10-11'),
(6, 9, '18:45:12', '2024-11-05'),
(8, 12, '22:10:59', '2024-10-30'),
(10, 15, '07:30:15', '2024-09-20'),
(12, 18, '13:44:23', '2024-11-01'),
(14, 21, '16:27:10', '2024-08-18'),
(16, 23, '19:59:45', '2024-07-14'),
(16, 13, '11:13:37', '2024-10-25'),
(4, 5, '08:45:52', '2024-12-03'),
(6, 3, '21:30:00', '2024-11-19'),
(8, 6, '06:05:15', '2024-09-11'),
(10, 19, '17:22:33', '2024-10-29'),
(12, 12, '12:10:55', '2024-11-24'),
(14, 14, '23:45:22', '2024-12-01');