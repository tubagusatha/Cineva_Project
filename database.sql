CREATE DATABASE IF NOT EXISTS cineva;
USE cineva;

-- =====================
-- TABLES
-- =====================

-- Genres
CREATE TABLE genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

-- Movies
CREATE TABLE movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    genre_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    poster_url VARCHAR(500) DEFAULT '',
    year INT,
    rating DECIMAL(3,1) DEFAULT 0,
    duration INT DEFAULT 0,
    trailer_url VARCHAR(500) DEFAULT '',
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Actors
CREATE TABLE actors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    bio TEXT,
    url_photo VARCHAR(500) DEFAULT '',
    birthdate DATE
) ENGINE=InnoDB;

-- Movie Actors (association class)
CREATE TABLE movie_actors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    actor_id INT NOT NULL,
    `character` VARCHAR(255),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES actors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Users (base entity; role: 'admin' | 'user')
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admins (extends Users)
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    deleted_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Customers (extends Users)
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    subscription ENUM('free','basic','standard','premium') DEFAULT 'free',
    subscription_expires_at DATETIME NULL,
    active_order_id VARCHAR(100) NULL,
    payment_method VARCHAR(50) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Subscription Transactions (Midtrans Payment Records)
CREATE TABLE subscription_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id VARCHAR(100) NOT NULL UNIQUE,
    plan ENUM('basic','standard','premium') NOT NULL,
    amount INT NOT NULL,
    snap_token VARCHAR(255) NULL,
    payment_type VARCHAR(50) DEFAULT 'midtrans',
    transaction_status ENUM('pending','settlement','capture','expire','cancel','deny') DEFAULT 'pending',
    pdf_url VARCHAR(500) NULL,
    payment_response LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Watch History
CREATE TABLE watch_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    progress INT DEFAULT 0,
    watched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- =====================
-- SEED DATA
-- =====================

-- Genres
INSERT INTO genres (name, description) VALUES
('Action', 'High-energy films with intense physical stunts, chases, battles, and fight sequences.'),
('Drama', 'Character-driven stories exploring complex emotions, relationships, and real-life situations.'),
('Sci-Fi', 'Speculative narratives exploring futuristic technology, space exploration, and scientific concepts.'),
('Comedy', 'Light-hearted films designed to entertain and provoke laughter through humor and wit.'),
('Horror', 'Films designed to frighten, unsettle, and invoke hidden fears through suspense and terror.'),
('Thriller', 'Suspenseful films that keep audiences on edge with tension, twists, and unpredictable plots.'),
('Romance', 'Stories centered around love, passion, and emotional connections between characters.'),
('Animation', 'Films created using animation techniques, ranging from hand-drawn to computer-generated imagery.'),
('Crime', 'Stories revolving around criminal activities, investigations, and the justice system.'),
('Adventure', 'Exciting journeys and quests filled with exploration, danger, and discovery.');

-- Movies
INSERT INTO movies (genre_id, title, description, year, rating, duration, trailer_url, is_featured) VALUES
(1, 'The Dark Knight', 'When the menace known as the Joker wreaks havoc on Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.', 2008, 9.0, 152, 'https://www.youtube.com/watch?v=EXeTwQWrcwY', 1),
(3, 'Inception', 'A thief who steals corporate secrets through dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.', 2010, 8.8, 148, 'https://www.youtube.com/watch?v=YoHD9XEInc0', 1),
(3, 'Interstellar', 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity''s survival as Earth becomes uninhabitable.', 2014, 8.7, 169, 'https://www.youtube.com/watch?v=zSWdZVtXT7E', 1),
(2, 'The Shawshank Redemption', 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.', 1994, 9.3, 142, 'https://www.youtube.com/watch?v=PLl99DlL6b4', 0),
(9, 'Pulp Fiction', 'The lives of two mob hitmen, a boxer, a gangster and his wife, and a pair of diner bandits intertwine in four tales of violence and redemption.', 1994, 8.9, 154, 'https://www.youtube.com/watch?v=s7EdQ4FqbhY', 0),
(2, 'Fight Club', 'An insomniac office worker and a devil-may-care soap maker form an underground fight club that evolves into much more.', 1999, 8.8, 139, 'https://www.youtube.com/watch?v=SUXWAEX2jlg', 0),
(3, 'The Matrix', 'A computer hacker learns about the true nature of his reality and his role in the war against its controllers.', 1999, 8.7, 136, 'https://www.youtube.com/watch?v=vKQi3bBA1y8', 0),
(6, 'Parasite', 'Greed and class discrimination threaten the newly formed symbiotic relationship between the wealthy Park family and the destitute Kim clan.', 2019, 8.5, 132, 'https://www.youtube.com/watch?v=5xH0HfJHsaY', 0),
(1, 'Avengers: Endgame', 'After the devastating events of Infinity War, the universe is in ruins. With the help of remaining allies, the Avengers assemble once more to reverse Thanos'' actions.', 2019, 8.4, 181, 'https://www.youtube.com/watch?v=TcMBFSGVi1c', 0),
(2, 'Joker', 'A mentally troubled standup comedian embarks on a downward spiral that leads to the creation of an iconic villain in Gotham City.', 2019, 8.4, 122, 'https://www.youtube.com/watch?v=zAGVQLHvwOY', 0),
(3, 'Dune', 'Paul Atreides, a brilliant and gifted young man born into a great destiny beyond his understanding, must travel to the most dangerous planet in the universe to ensure the future of his family and his people.', 2021, 8.0, 155, 'https://www.youtube.com/watch?v=8g18jFHCLXk', 1),
(2, 'Oppenheimer', 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb during World War II.', 2023, 8.3, 180, 'https://www.youtube.com/watch?v=uYPbbksJxIg', 0),
(1, 'Spider-Man: No Way Home', 'With Spider-Man''s identity now revealed, Peter asks Doctor Strange for help. When a spell goes wrong, dangerous foes from other worlds start to appear.', 2021, 8.2, 148, 'https://www.youtube.com/watch?v=JfVOs4VSpmA', 0),
(9, 'The Godfather', 'The aging patriarch of an organized crime dynasty transfers control of his clandestine empire to his reluctant youngest son.', 1972, 9.2, 175, 'https://www.youtube.com/watch?v=UaVTIH8mujA', 0),
(2, 'Forrest Gump', 'The presidencies of Kennedy and Johnson, the Vietnam War, and other historical events unfold from the perspective of an Alabama man with a limited IQ.', 1994, 8.8, 142, 'https://www.youtube.com/watch?v=bLvqoHBptjg', 0),
(1, 'Gladiator', 'A former Roman General sets out to exact vengeance against the corrupt emperor who murdered his family and sent him into slavery.', 2000, 8.5, 155, 'https://www.youtube.com/watch?v=owK1qxDselE', 0),
(6, 'The Silence of the Lambs', 'A young FBI cadet must receive the help of an incarcerated and manipulative cannibal killer to help catch another serial killer.', 1991, 8.6, 118, 'https://www.youtube.com/watch?v=W6GDil0rGls', 0),
(5, 'Get Out', 'A young African-American visits his white girlfriend''s parents for the weekend, where his simmering uneasiness about their reception of him eventually reaches a boiling point.', 2017, 7.7, 104, 'https://www.youtube.com/watch?v=DzfpyUB60YY', 0),
(7, 'La La Land', 'While navigating their careers in Los Angeles, a pianist and an actress fall in love while attempting to reconcile their aspirations for the future.', 2016, 8.0, 128, 'https://www.youtube.com/watch?v=0pdqf4P9MB8', 0),
(4, 'The Grand Budapest Hotel', 'A writer encounters the owner of an aging high-class hotel, who tells him of his early years serving as a lobby boy in the hotel''s glorious years.', 2014, 8.1, 99, 'https://www.youtube.com/watch?v=1Fg5iWmQjwk', 0),
(1, 'John Wick', 'An ex-hitman comes out of retirement to track down the gangsters that killed his dog and took everything from him.', 2014, 7.4, 101, 'https://www.youtube.com/watch?v=C0BMx-qxsP4', 0),
(3, 'Blade Runner 2049', 'A young blade runner''s discovery of a long-buried secret leads him to track down former blade runner Rick Deckard, who''s been missing for thirty years.', 2017, 8.0, 164, 'https://www.youtube.com/watch?v=gCcx85zbxz4', 0),
(2, 'Whiplash', 'A promising young drummer enrolls at a cut-throat music conservatory where his dreams of greatness are mentored by an instructor who will stop at nothing to realize a student''s potential.', 2014, 8.5, 107, 'https://www.youtube.com/watch?v=7d_jQycdQGo', 0),
(1, 'Mad Max: Fury Road', 'In a post-apocalyptic wasteland, a woman rebels against a tyrannical ruler in search for her homeland with the aid of a group of female prisoners and a lone drifter.', 2015, 8.1, 120, 'https://www.youtube.com/watch?v=hEJnMQG9ev8', 0),
(5, 'The Conjuring', 'Paranormal investigators Ed and Lorraine Warren work to help a family terrorized by a dark presence in their farmhouse.', 2013, 7.5, 112, 'https://www.youtube.com/watch?v=k10ETZ41q5o', 0),
(3, 'Everything Everywhere All at Once', 'An aging Chinese immigrant is swept up in an insane adventure where she alone can save the world by exploring other universes connecting with the lives she could have led.', 2022, 7.8, 139, 'https://www.youtube.com/watch?v=wxN1T1qdQ', 0),
(1, 'Top Gun: Maverick', 'After thirty years of service, Pete Mitchell is where he belongs, pushing the envelope as a courageous test pilot and dodging the advancement in rank that would ground him.', 2022, 8.2, 131, 'https://www.youtube.com/watch?v=qSqVVswa420', 0),
(8, 'Coco', 'Aspiring musician Miguel, confronted with his family''s ancestral ban on music, enters the Land of the Dead to find his great-great-grandfather, a legendary singer.', 2017, 8.4, 105, 'https://www.youtube.com/watch?v=Rvr68u6k5sI', 0),
(8, 'Your Name', 'Two strangers find themselves linked in a bizarre way. When a connection forms, will distance be the only thing to keep them apart?', 2016, 8.4, 106, 'https://www.youtube.com/watch?v=xU47nhruN-Q', 0),
(1, 'The Batman', 'When a sadistic serial killer begins murdering key political figures in Gotham, Batman is forced to investigate the city''s hidden corruption and question his family''s involvement.', 2022, 7.8, 176, 'https://www.youtube.com/watch?v=mqqft2x_Aa4', 0);

-- Actors
INSERT INTO actors (name, bio, birthdate) VALUES
('Christian Bale', 'English actor known for his versatility and intense method acting approach.', '1974-01-30'),
('Heath Ledger', 'Australian actor whose portrayal of the Joker became one of cinema''s most iconic performances.', '1979-04-04'),
('Leonardo DiCaprio', 'American actor and producer known for his work in biopics and period films.', '1974-11-11'),
('Morgan Freeman', 'American actor, director, and narrator known for his distinctive deep voice.', '1937-06-01'),
('Tim Robbins', 'American actor and filmmaker known for his roles in drama films.', '1958-10-16'),
('John Travolta', 'American actor and singer known for his diverse film career spanning decades.', '1954-02-18'),
('Samuel L. Jackson', 'American actor widely regarded as one of the most popular actors of his generation.', '1948-12-21'),
('Keanu Reeves', 'Canadian actor known for his roles in action and science fiction films.', '1964-09-02'),
('Robert Downey Jr.', 'American actor known for his charismatic performances and remarkable career comeback.', '1965-04-04'),
('Joaquin Phoenix', 'American actor known for his intense and unconventional performances.', '1974-10-28'),
('Timothée Chalamet', 'American actor known for his roles in independent and blockbuster films.', '1995-12-27'),
('Cillian Murphy', 'Irish actor known for his striking blue eyes and versatile acting range.', '2004-05-25'),
('Tom Holland', 'English actor known for playing Spider-Man in the Marvel Cinematic Universe.', '1996-06-01'),
('Marlon Brando', 'American actor considered one of the greatest and most influential actors of the 20th century.', '1924-04-03'),
('Tom Hanks', 'American actor and filmmaker known for his comedic and dramatic roles.', '1956-07-09'),
('Russell Crowe', 'New Zealand-born Australian actor known for his intense performances.', '1964-04-07'),
('Anthony Hopkins', 'Welsh actor known for his formidable and menacing roles.', '1937-12-31'),
('Ryan Gosling', 'Canadian actor known for his roles in independent and mainstream films.', '1980-11-12'),
('Emma Stone', 'American actress known for her comedic and dramatic versatility.', '1988-11-06'),
('Song Kang-ho', 'South Korean actor considered one of the greatest actors in Korean cinema.', '1967-01-17');

-- Movie Actors
INSERT INTO movie_actors (movie_id, actor_id, `character`) VALUES
(1, 1, 'Bruce Wayne / Batman'),
(1, 2, 'The Joker'),
(1, 4, 'Lucius Fox'),
(2, 3, 'Dom Cobb'),
(3, 3, 'N/A'),
(4, 4, 'Ellis Boyd Redding'),
(4, 5, 'Andy Dufresne'),
(5, 6, 'Vincent Vega'),
(5, 7, 'Jules Winnfield'),
(7, 8, 'Neo'),
(8, 20, 'Kim Ki-taek'),
(9, 9, 'Tony Stark / Iron Man'),
(10, 10, 'Arthur Fleck / Joker'),
(11, 11, 'Paul Atreides'),
(12, 12, 'J. Robert Oppenheimer'),
(13, 13, 'Peter Parker / Spider-Man'),
(14, 14, 'Don Vito Corleone'),
(15, 15, 'Forrest Gump'),
(16, 16, 'Maximus Decimus Meridius'),
(17, 17, 'Dr. Hannibal Lecter'),
(19, 18, 'Sebastian Wilder'),
(19, 19, 'Mia Dolan'),
(21, 8, 'John Wick'),
(24, 15, 'Max Rockatansky');

-- Users base table (password: admin123 / user123)
INSERT INTO users (name, email, password, role) VALUES
('Administrator', 'admin@cineva.com', '$2y$10$QEZGb.vMYqKqo0DVHwCYPuiTjWV.BKZbFD51sltUAgVncEy9S.BdW', 'admin'),
('John Doe', 'user@cineva.com', '$2y$10$vSMhQSWqkaTGB4dowraNS.J9.jpVX1a2rbwCmhRnOYJX7zsvkQKQG', 'user'),
('Jane Smith', 'jane@cineva.com', '$2y$10$vSMhQSWqkaTGB4dowraNS.J9.jpVX1a2rbwCmhRnOYJX7zsvkQKQG', 'user');

-- Admins (sub-entity of Users)
INSERT INTO admins (user_id, name, email, password, status) VALUES
(1, 'Administrator', 'admin@cineva.com', '$2y$10$QEZGb.vMYqKqo0DVHwCYPuiTjWV.BKZbFD51sltUAgVncEy9S.BdW', 'active');

-- Customers (sub-entity of Users)
INSERT INTO customers (user_id, name, email, password, subscription, subscription_expires_at, active_order_id, payment_method) VALUES
(2, 'John Doe', 'user@cineva.com', '$2y$10$vSMhQSWqkaTGB4dowraNS.J9.jpVX1a2rbwCmhRnOYJX7zsvkQKQG', 'premium', DATE_ADD(NOW(), INTERVAL 28 DAY), 'CINEVA-PREM-1001', 'qris'),
(3, 'Jane Smith', 'jane@cineva.com', '$2y$10$vSMhQSWqkaTGB4dowraNS.J9.jpVX1a2rbwCmhRnOYJX7zsvkQKQG', 'basic', DATE_ADD(NOW(), INTERVAL 14 DAY), 'CINEVA-BASC-1002', 'bank_transfer');

-- Sample Midtrans Subscription Transactions
INSERT INTO subscription_transactions (user_id, order_id, plan, amount, payment_type, transaction_status, paid_at) VALUES
(2, 'CINEVA-PREM-1001', 'premium', 149000, 'qris', 'settlement', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'CINEVA-BASC-1002', 'basic', 49000, 'bank_transfer', 'settlement', DATE_SUB(NOW(), INTERVAL 16 DAY));

-- Watch History
INSERT INTO watch_history (user_id, movie_id, progress, watched_at) VALUES
(2, 1, 85, '2025-09-20 14:30:00'),
(2, 2, 100, '2025-09-19 20:00:00'),
(2, 3, 45, '2025-09-18 21:15:00'),
(2, 11, 60, '2025-09-21 19:00:00'),
(3, 1, 100, '2025-09-15 22:00:00'),
(3, 4, 100, '2025-09-16 18:30:00'),
(3, 10, 30, '2025-09-20 20:45:00');
