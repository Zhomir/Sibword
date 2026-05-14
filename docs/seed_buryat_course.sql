-- Seed script for Buryat course content
-- Compatible with current MVP schema (courses -> modules -> lessons -> lesson_steps)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

-- 1) Ensure language exists
INSERT INTO languages (code, name, is_active, created_at, updated_at)
SELECT 'bxr', 'Buryat language (Buryat)', 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM languages WHERE code = 'bxr'
);

SET @lang_id := (SELECT id FROM languages WHERE code = 'bxr' ORDER BY id LIMIT 1);
SET @teacher_id := (SELECT id FROM users WHERE role = 'teacher' ORDER BY id LIMIT 1);

-- 2) Course
INSERT INTO courses (
    language_id, title, description, level, status, visibility, created_by, moderated_by, moderated_at, created_at, updated_at
) VALUES (
    @lang_id,
    'Буряад хэлэн – алхам алхамаар',
    'Интерактивный курс бурятского языка: знакомство, одежда, дом, природа, числа и еда. Включает теорию, диалоги и практические задания.',
    'beginner',
    'published',
    'public',
    @teacher_id,
    @teacher_id,
    NOW(),
    NOW(),
    NOW()
);

SET @course_id := LAST_INSERT_ID();

-- Optional teacher binding if teacher exists
INSERT INTO course_teachers (course_id, teacher_id, can_edit, created_at, updated_at)
SELECT @course_id, @teacher_id, 1, NOW(), NOW()
WHERE @teacher_id IS NOT NULL;

-- 3) Modules
INSERT INTO course_modules (course_id, title, description, order_num, created_at, updated_at)
VALUES
(@course_id, 'Танилсалга ба бэеын хубинууд', 'Знакомство, части тела и одежда', 1, NOW(), NOW()),
(@course_id, 'Гэр байра ба байгаали', 'Дом, быт и природные явления', 2, NOW(), NOW()),
(@course_id, 'Тоонууд, эдеэн, үйлэ үгэнүүд', 'Числа, еда и разговорная практика', 3, NOW(), NOW());

SET @m1 := (SELECT id FROM course_modules WHERE course_id = @course_id AND order_num = 1 LIMIT 1);
SET @m2 := (SELECT id FROM course_modules WHERE course_id = @course_id AND order_num = 2 LIMIT 1);
SET @m3 := (SELECT id FROM course_modules WHERE course_id = @course_id AND order_num = 3 LIMIT 1);

-- 4) Lessons
INSERT INTO lessons (module_id, title, theory_content, lesson_type, order_num, status, estimated_minutes, created_at, updated_at)
VALUES
(@m1, 'Урок 1.1. Үглөөнэй хушаалта', 'Части тела, одежда, притяжательные местоимения и рассказ о своём утре.', 'standard', 1, 'published', 35, NOW(), NOW()),
(@m1, 'Урок 1.2. Хубсаhан ба өнгөнүүд', 'Одежда и цвета, согласование в словосочетаниях.', 'standard', 2, 'published', 35, NOW(), NOW()),
(@m2, 'Урок 2.1. Манай гэр ба хотон', 'Дом, предметы быта, местный и дательный падеж.', 'standard', 1, 'published', 35, NOW(), NOW()),
(@m2, 'Урок 2.2. Байгаалиин үзэгдэлнүүд', 'Погода, времена года, безличные предложения.', 'standard', 2, 'published', 35, NOW(), NOW()),
(@m3, 'Урок 3.1. Хэды? Хэдэдэхи? ба эдеэн ууха', 'Числа, порядковые числительные, еда и посуда.', 'standard', 1, 'published', 40, NOW(), NOW());

SET @l11 := (SELECT id FROM lessons WHERE module_id = @m1 AND order_num = 1 LIMIT 1);
SET @l12 := (SELECT id FROM lessons WHERE module_id = @m1 AND order_num = 2 LIMIT 1);
SET @l21 := (SELECT id FROM lessons WHERE module_id = @m2 AND order_num = 1 LIMIT 1);
SET @l22 := (SELECT id FROM lessons WHERE module_id = @m2 AND order_num = 2 LIMIT 1);
SET @l31 := (SELECT id FROM lessons WHERE module_id = @m3 AND order_num = 1 LIMIT 1);

-- 5) Lesson 1.1 steps
INSERT INTO lesson_steps (lesson_id, step_type, title, prompt, config_json, order_num, created_at, updated_at)
VALUES
(@l11, 'text', 'Үглөөнэй хушаалта', NULL,
 JSON_OBJECT('frontend_step', JSON_OBJECT(
    'type','theory',
    'title','Үглөөнэй хушаалта',
    'content','<p>Энэ үглөө. Түргэн бодоно, нюдэгээ үдэнэ, шүдээ угаана.</p><p>Эжы: «Шамда саг болоо! Хубсаhанаа үмдэ!»</p><p><b>Үгэнүүд:</b> нюдэн, шүдэн, гар, хүл, толгой, дэгэл, үмдэн, малгай, бээлэй, гутал.</p><p><b>Грамматика:</b> минии нюдэн, шинии шүдэн, тэрэнэй гар, манай дэгэл, танай гутал.</p>'
 )),
 1, NOW(), NOW()),
(@l11, 'multiple_choice', NULL, 'Выбери правильный перевод слова «малгай».',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','multiple_choice',
   'question','Выбери правильный перевод слова «малгай».',
   'options', JSON_ARRAY('шапка','варежки','обувь','рубашка'),
   'correct_idx', 0
 )),
 2, NOW(), NOW()),
(@l11, 'matching_pairs', NULL, 'Соотнеси бурятские слова и перевод.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','matching',
   'question','Соотнеси слова: нюдэн, шүдэн, гар, хүл.',
   'left', JSON_ARRAY(
      JSON_OBJECT('id',1,'text','нюдэн'),
      JSON_OBJECT('id',2,'text','шүдэн'),
      JSON_OBJECT('id',3,'text','гар'),
      JSON_OBJECT('id',4,'text','хүл')
   ),
   'right', JSON_ARRAY(
      JSON_OBJECT('id',1,'text','глаз'),
      JSON_OBJECT('id',2,'text','зуб'),
      JSON_OBJECT('id',3,'text','рука'),
      JSON_OBJECT('id',4,'text','нога')
   )
 )),
 3, NOW(), NOW()),
(@l11, 'fill_gaps', NULL, 'Заполни пропуск.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','fill_blanks',
   'question','Заполни пропуск: «___ гутал үүдэн дээрэ байна».',
   'sentence','___ гутал үүдэн дээрэ байна.',
   'options', JSON_ARRAY('Шамдай','Минии','Тэрэнэй','Манай'),
   'correct_answer','Шамдай'
 )),
 4, NOW(), NOW());

-- 6) Lesson 1.2 steps
INSERT INTO lesson_steps (lesson_id, step_type, title, prompt, config_json, order_num, created_at, updated_at)
VALUES
(@l12, 'text', 'Хубсаhан ба өнгөнүүд', NULL,
 JSON_OBJECT('frontend_step', JSON_OBJECT(
    'type','theory',
    'title','Хубсаhан ба өнгөнүүд',
    'content','<p>Наадан дэлгүүртэ олон хубсаhан байна: улаан, шара, ногоон, хүхэ, сагаан.</p><p><b>Үгэнүүд:</b> самса, бүhэ, заха, оймhон.</p><p><b>Дүрим:</b> прилагательное стоит перед существительным: улаан малгай, сагаан дэгэл.</p>'
 )),
 1, NOW(), NOW()),
(@l12, 'multiple_choice', NULL, 'Выбери правильный вариант.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','multiple_choice',
   'question','Как будет «зелёная рубашка»?',
   'options', JSON_ARRAY('ногоон самса','самса ногоон','ногоон дэгэл','сагаан самса'),
   'correct_idx', 0
 )),
 2, NOW(), NOW()),
(@l12, 'fill_gaps', NULL, 'Заполни пропуск.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','fill_blanks',
   'question','Заполни: «___ дэгэл» (белая шуба).',
   'sentence','___ дэгэл',
   'options', JSON_ARRAY('сагаан','улаан','шара','хүхэ'),
   'correct_answer','сагаан'
 )),
 3, NOW(), NOW()),
(@l12, 'word_order', NULL, 'Собери фразу в правильном порядке.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','word_order',
   'question','Собери: «У меня красивая шапка».',
   'options', JSON_ARRAY('Минии','малгай','hайхан'),
   'correct_answer', JSON_ARRAY('Минии','малгай','hайхан')
 )),
 4, NOW(), NOW());

-- 7) Lesson 2.1 steps
INSERT INTO lesson_steps (lesson_id, step_type, title, prompt, config_json, order_num, created_at, updated_at)
VALUES
(@l21, 'text', 'Манай гэр ба хотон', NULL,
 JSON_OBJECT('frontend_step', JSON_OBJECT(
    'type','theory',
    'title','Манай гэр ба хотон',
    'content','<p>Манай гэр – юрта. Хана модон, үүдэн зүгөөр наран ородог.</p><p>Пеэшэн дээрэ тогоон байна. Хажууда шэрээ, hандали байна.</p><p><b>Падежи:</b> гэртээ, ханада, үүдэн дээрэ, хотоноосоо.</p>'
 )),
 1, NOW(), NOW()),
(@l21, 'matching_pairs', NULL, 'Соотнеси слова дома и перевод.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','matching',
   'question','Соотнеси: гэр, хана, үүдэн, сонхо, пеэшэн.',
   'left', JSON_ARRAY(
      JSON_OBJECT('id',1,'text','гэр'),
      JSON_OBJECT('id',2,'text','хана'),
      JSON_OBJECT('id',3,'text','үүдэн'),
      JSON_OBJECT('id',4,'text','сонхо'),
      JSON_OBJECT('id',5,'text','пеэшэн')
   ),
   'right', JSON_ARRAY(
      JSON_OBJECT('id',1,'text','дом'),
      JSON_OBJECT('id',2,'text','стена'),
      JSON_OBJECT('id',3,'text','дверь'),
      JSON_OBJECT('id',4,'text','окно'),
      JSON_OBJECT('id',5,'text','печь')
   )
 )),
 2, NOW(), NOW()),
(@l21, 'fill_gaps', NULL, 'Заполни пропуск с местным падежом.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','fill_blanks',
   'question','Заполни: «Би ___ hуунаб». (я сижу дома)',
   'sentence','Би ___ hуунаб.',
   'options', JSON_ARRAY('гэртээ','гэр','гэртэ','гэрээр'),
   'correct_answer','гэртээ'
 )),
 3, NOW(), NOW()),
(@l21, 'multiple_choice', NULL, 'Выбери правильный перевод.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','multiple_choice',
   'question','«Хотоноосоо» — это:',
   'options', JSON_ARRAY('из своего хлева','в свой хлев','на своём хлеве','у хлева'),
   'correct_idx', 0
 )),
 4, NOW(), NOW());

-- 8) Lesson 2.2 steps
INSERT INTO lesson_steps (lesson_id, step_type, title, prompt, config_json, order_num, created_at, updated_at)
VALUES
(@l22, 'text', 'Байгаалиин үзэгдэлнүүд', NULL,
 JSON_OBJECT('frontend_step', JSON_OBJECT(
    'type','theory',
    'title','Байгаалиин үзэгдэлнүүд',
    'content','<p>Үбэл. Саhан ороно, хүйтэн hалхин үлээжэ байна.</p><p><b>Үгэнүүд:</b> хабар, зун, намар, үбэл; наран, hара, тэнгэри; бороо, саhан, hалхин.</p><p><b>Жэшээ:</b> Саhан ороно. Бороо оробо. Һалхин үлээжэ байна.</p>'
 )),
 1, NOW(), NOW()),
(@l22, 'multiple_choice', NULL, 'Выбери правильный перевод.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','multiple_choice',
   'question','Как будет «идёт снег»?',
   'options', JSON_ARRAY('Саhан ороно','Бороо ороно','Наран гарна','Һалхин зогсоно'),
   'correct_idx', 0
 )),
 2, NOW(), NOW()),
(@l22, 'fill_gaps', NULL, 'Заполни пропуск.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','fill_blanks',
   'question','Заполни: «___ үлээжэ байна».',
   'sentence','___ үлээжэ байна.',
   'options', JSON_ARRAY('Һалхин','Наран','Һара','Мүшэн'),
   'correct_answer','Һалхин'
 )),
 3, NOW(), NOW()),
(@l22, 'matching_pairs', NULL, 'Соотнеси сезон и перевод.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','matching',
   'question','Соотнеси: хабар, зун, намар, үбэл.',
   'left', JSON_ARRAY(
      JSON_OBJECT('id',1,'text','хабар'),
      JSON_OBJECT('id',2,'text','зун'),
      JSON_OBJECT('id',3,'text','намар'),
      JSON_OBJECT('id',4,'text','үбэл')
   ),
   'right', JSON_ARRAY(
      JSON_OBJECT('id',1,'text','весна'),
      JSON_OBJECT('id',2,'text','лето'),
      JSON_OBJECT('id',3,'text','осень'),
      JSON_OBJECT('id',4,'text','зима')
   )
 )),
 4, NOW(), NOW());

-- 9) Lesson 3.1 steps (including tasks from docs/text)
INSERT INTO lesson_steps (lesson_id, step_type, title, prompt, config_json, order_num, created_at, updated_at)
VALUES
(@l31, 'text', 'Хэды? Хэдэдэхи? ба эдеэн ууха', NULL,
 JSON_OBJECT('frontend_step', JSON_OBJECT(
    'type','theory',
    'title','Хэды? Хэдэдэхи? ба эдеэн ууха',
    'content','<p>Числа: нэгэн, хоёр, гурбан ... арбан ... зуун.</p><p>Порядковые: нэгэдэхи, хоёрдохи, гурбадахи, дүрбэдэхи.</p><p><b>Лексика:</b> сай, hүн, тоhон, зөөхэй, бууза, аяга, табаг, халбага, гүсэ.</p><p>Диалог в кафе: закажи чай и две буузы, спроси цену.</p>'
 )),
 1, NOW(), NOW()),
(@l31, 'multiple_choice', NULL, 'Выбери правильный порядковый числительный.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','multiple_choice',
   'question','Как правильно: «четвёртый»?',
   'options', JSON_ARRAY('дүрбэдэхи','дүрбэн','дүрбэдэ','дүрбэндэ'),
   'correct_idx', 0
 )),
 2, NOW(), NOW()),
(@l31, 'fill_gaps', NULL, 'Вставь слово.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','fill_blanks',
   'question','Вставь: «Нэгэ халбага элсэн сахар, хоёр аяга ___».',
   'sentence','Нэгэ халбага элсэн сахар, хоёр аяга ___.',
   'options', JSON_ARRAY('hүн','сай','тоhон','зөөхэй'),
   'correct_answer','hүн'
 )),
 3, NOW(), NOW()),
(@l31, 'word_order', NULL, 'Собери фразу заказа в кафе.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
   'type','task','task_type','word_order',
   'question','Собери: «Намда хоёр бууза, нэгэ аяга сай үгыт».',
   'options', JSON_ARRAY('Намда','хоёр','бууза,','нэгэ','аяга','сай','үгыт'),
   'correct_answer', JSON_ARRAY('Намда','хоёр','бууза,','нэгэ','аяга','сай','үгыт')
 )),
 4, NOW(), NOW()),
(@l31, 'fill_gaps', NULL, 'Напиши числительное словами.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
    'type','task','task_type','fill_blanks',
    'question','Напиши словами число 15.',
    'sentence','15 = ___',
    'options', JSON_ARRAY('арбан табан','арбан дүрбэн','табин','хорин табан'),
    'correct_answer','арбан табан'
 )),
 5, NOW(), NOW()),
(@l31, 'multiple_choice', NULL, 'Выбери правильный перевод фразы.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
    'type','task','task_type','multiple_choice',
    'question','«Мой четвёртый дневник» — это:',
    'options', JSON_ARRAY('Минии дүрбэдэхи дневник','Шинии дүрбэдэхи дневник','Минии дүрбэн дневник','Минии хоёрдохи дневник'),
    'correct_idx', 0
 )),
 6, NOW(), NOW()),
(@l31, 'multiple_choice', NULL, 'Реши пример на бурятском.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
    'type','task','task_type','multiple_choice',
    'question','12 + 8 = ?',
    'options', JSON_ARRAY('хорин','арбан долоон','арбан найман','гушан'),
    'correct_idx', 0
 )),
 7, NOW(), NOW()),
(@l31, 'multiple_choice', NULL, 'Выбери реплику для заказа в кафе.',
 JSON_OBJECT('frontend_step', JSON_OBJECT(
    'type','task','task_type','multiple_choice',
    'question','Как правильно заказать чай и две буузы и спросить цену?',
    'options', JSON_ARRAY(
      'Намда хоёр бууза, нэгэ аяга сай үгыт. Хэды үнэтэйб?',
      'Намда нэгэ бууза үгыт. Би уhа уунаб.',
      'Би гэртээ ошоноб. Хэды hурагшад бэ?',
      'Намда табан малгай үгыт. Хэды үдэр бэ?'
    ),
    'correct_idx', 0
 )),
 8, NOW(), NOW());

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
