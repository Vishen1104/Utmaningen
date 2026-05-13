CREATE DATABASE IF NOT EXISTS poker;
USE poker;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    chips INT DEFAULT 1000
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    status ENUM('waiting','playing','finished') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE players_in_room (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    user_id INT,
    seat INT,
    chips INT DEFAULT 1000,
    status ENUM('active','folded','all-in','out') DEFAULT 'active',
    hand TEXT,
    current_bet INT DEFAULT 0,
    total_bet INT DEFAULT 0
);

CREATE TABLE game_state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNIQUE,
    deck TEXT,
    community_cards TEXT,
    pot INT DEFAULT 0,
    current_player INT,
    round ENUM('preflop','flop','turn','river','showdown'),
    current_bet INT DEFAULT 0,
    dealer_seat INT DEFAULT 0,
    winner_hand VARCHAR(255) DEFAULT NULL
);