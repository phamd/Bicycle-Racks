/**
    # Users schema notes:
    PHP's password_hash() uses a random salt that will be concatenated (along with other variables) into the final hash; so with this, we don't need a separate, dedicated SALT column.
    PHP's password_hash($pass, PASSWORD_BCRYPT) uses BCRYPT with output length of 60.
    Note with bcrypt, the unhashed password length is max 72.
    Emails have a max length of 254, but we'll round up to 255.
 */
CREATE TABLE Users (
id           INT           NOT NULL UNIQUE AUTO_INCREMENT,
username     VARCHAR (20)  NOT NULL UNIQUE,
email        VARCHAR (255) NOT NULL UNIQUE,
passwordhash CHAR (60)     NOT NULL,
PRIMARY KEY (id)
);

/**
    # Objects schema notes:
    No S3 picture keys will be stored in the Objects table, since the S3 images will be organized by the Object's id,
    and I have the assumption of one image per object.
    Google Maps API recommended FLOAT (10,6) for lat and lng.
 */
CREATE TABLE Objects (
id          INT           NOT NULL UNIQUE AUTO_INCREMENT,
name        VARCHAR (32)  NOT NULL,
latitude    FLOAT (10,6)  NOT NULL,
longitude   FLOAT (10,6)  NOT NULL,
PRIMARY     KEY (id)
);

/**
    # Reviews schema notes:
    Comments are of type VARCHAR (255) instead of TEXT for speed and since even Twitter has a limit of 140.
    I used TIMESTAMP instead of DATETIME so that I can use the default CURRENT_TIMESTAMP.
    TIMESTAMP columns default to be NOT NULL.
 */
CREATE TABLE Reviews (
id           INT            NOT NULL UNIQUE AUTO_INCREMENT,
rating       TINYINT        NOT NULL,
comment      VARCHAR (255)  NOT NULL,
date         TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
objectid     INT            NOT NULL,
userid       INT            NOT NULL,
PRIMARY KEY (id),
FOREIGN KEY (objectid) REFERENCES Objects(id),
FOREIGN KEY (userid) REFERENCES Users(id)
);
