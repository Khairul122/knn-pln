-- 004: KNN model storage & prediction results
CREATE TABLE IF NOT EXISTS knn_models (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tahun           YEAR            NOT NULL,
    k_value         TINYINT UNSIGNED NOT NULL DEFAULT 5,
    feature_columns VARCHAR(100)    NOT NULL DEFAULT 'severity,occurrence,detection',
    distance_metric VARCHAR(20)     NOT NULL DEFAULT 'euclidean',
    train_count     INT UNSIGNED    DEFAULT 0,
    test_count      INT UNSIGNED    DEFAULT 0,
    accuracy        DECIMAL(6,4)    NULL,
    precision_score DECIMAL(6,4)    NULL,
    recall_score    DECIMAL(6,4)    NULL,
    f1_score        DECIMAL(6,4)    NULL,
    model_path      VARCHAR(500)    NULL,
    trained_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    trained_by      INT UNSIGNED    NULL,
    INDEX idx_tahun (tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS knn_predictions (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    model_id        INT UNSIGNED    NOT NULL,
    pemeliharaan_id INT UNSIGNED    NOT NULL,
    predicted_label ENUM('Rendah','Sedang','Tinggi') NOT NULL,
    actual_label    ENUM('Rendah','Sedang','Tinggi') NULL,
    confidence      DECIMAL(5,4)    NULL,
    neighbors_json  TEXT            NULL,
    predicted_at    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_model_pem (model_id, pemeliharaan_id),
    INDEX idx_model (model_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
