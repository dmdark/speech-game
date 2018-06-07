CREATE TABLE interest
(
  id         INT AUTO_INCREMENT
    PRIMARY KEY,
  title      VARCHAR(32)                             NOT NULL,
  title_en   VARCHAR(32)                             NOT NULL,
  unicode    VARCHAR(6)                              NOT NULL,
  priority   INT                                     NOT NULL,
  is_active  TINYINT(1)                              NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP     NOT NULL,
  updated_at TIMESTAMP NULL,
  icon_name  VARCHAR(32)                             NULL
);

create table score
(
  interest_id int null,
  user_id int null,
  name varchar(255) null,
  score int null,
  created_at timestamp default CURRENT_TIMESTAMP null
);