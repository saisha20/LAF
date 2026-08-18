
USE foundly_db;

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    type             ENUM('match_approved','potential_match','claim_review','system') NOT NULL,
    title            VARCHAR(150) NOT NULL,
    message          TEXT NOT NULL,
    link_report_id   INT NULL,
    is_read          TINYINT(1) NOT NULL DEFAULT 0,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_notifications_report
        FOREIGN KEY (link_report_id) REFERENCES reports(report_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;
