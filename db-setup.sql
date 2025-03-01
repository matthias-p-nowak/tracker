use sys;

drop database if exists `tracker`;
create database `tracker`;

drop user if exists 'tracker'@'%';
create user 'tracker' identified by '481ffbd2b0';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES, EXECUTE, 
    CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EVENT, TRIGGER ON `diary`.* TO `diary`@`%`;
