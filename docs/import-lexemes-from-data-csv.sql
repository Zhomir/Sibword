-- Импорт словаря из PZMVP/data.csv в таблицу lexemes
-- Для MySQL 8+

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- 1) Гарантируем наличие языка bxr
INSERT INTO languages (code, name, is_active, created_at, updated_at)
SELECT 'bxr', 'Бурятский язык', 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM languages WHERE code = 'bxr'
);

SET @lang_id := (SELECT id FROM languages WHERE code = 'bxr' LIMIT 1);

-- 2) Временная таблица для сырого CSV
DROP TEMPORARY TABLE IF EXISTS tmp_lexemes_import;
CREATE TEMPORARY TABLE tmp_lexemes_import (
    src_id BIGINT NULL,
    word_raw TEXT NULL,
    translation_raw TEXT NULL,
    pos_raw TEXT NULL,
    topic_raw TEXT NULL,
    note_raw TEXT NULL,
    pack_raw TEXT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 3) Загрузка CSV
-- ВАЖНО: если после импорта текст будет "кракозябрами",
--        поменяй CHARACTER SET cp1251 на utf8mb4 и повтори.
LOAD DATA LOCAL INFILE 'C:/xampp/htdocs/MVPSibword/PZMVP/data.csv'
INTO TABLE tmp_lexemes_import
CHARACTER SET cp1251
FIELDS TERMINATED BY ','
OPTIONALLY ENCLOSED BY '"'
ESCAPED BY '"'
LINES TERMINATED BY '\n'
(@src_id, @word, @translation, @pos, @topic, @note, @pack)
SET
    src_id = NULLIF(TRIM(@src_id), ''),
    word_raw = NULLIF(TRIM(@word), ''),
    translation_raw = NULLIF(TRIM(@translation), ''),
    pos_raw = NULLIF(TRIM(@pos), ''),
    topic_raw = NULLIF(TRIM(@topic), ''),
    note_raw = NULLIF(TRIM(@note), ''),
    pack_raw = NULLIF(TRIM(@pack), '');

-- 4) Импорт в lexemes (без дублей по language_id + word + translation)
INSERT INTO lexemes (
    language_id,
    word,
    translation,
    transcription,
    complexity_index,
    status,
    created_at,
    updated_at
)
SELECT
    @lang_id AS language_id,
    LEFT(t.word_raw, 255) AS word,
    LEFT(t.translation_raw, 255) AS translation,
    NULL AS transcription,
    0.00 AS complexity_index,
    'published' AS status,
    NOW() AS created_at,
    NOW() AS updated_at
FROM tmp_lexemes_import t
WHERE t.word_raw IS NOT NULL
  AND t.translation_raw IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM lexemes l
      WHERE l.language_id = @lang_id
        AND l.word = LEFT(t.word_raw, 255)
        AND l.translation = LEFT(t.translation_raw, 255)
  );

-- 5) Проверка результата
SELECT COUNT(*) AS imported_rows
FROM lexemes
WHERE language_id = @lang_id;
