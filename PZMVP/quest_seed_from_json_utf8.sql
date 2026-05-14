-- Auto-generated from public/data/quest.json (UTF-8 safe)
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO quests (code, title, description, is_active, created_at, updated_at)
VALUES ('altan_zagalan', 'Алтан загалан', 'Импортировано из public/data/quest.json', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), is_active=VALUES(is_active), updated_at=NOW();

SET @quest_id := (SELECT id FROM quests WHERE code = 'altan_zagalan' LIMIT 1);

-- optional cleanup for re-import
DELETE qc FROM quest_choices qc JOIN quest_nodes qn ON qn.id = qc.node_id WHERE qn.quest_id = @quest_id;
DELETE FROM quest_nodes WHERE quest_id = @quest_id;

-- nodes
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'prologue', '🌊 Эртэ урда сагта нэгэ үбгэн хүгшэн хоёр далайн эрье дээрэ ажаһуудаг байгаа.

(Давным-давно жили-были старик со старухой у самого синего моря.)

Готовы ли вы войти в бурятскую сказку «Алтан загаһан» и выучить язык?', 1, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'practice_prologue', '📝 Практика: Сопоставьте бурятские слова с их переводом.

Выберите правильный вариант:', 2, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fishing_1', 'Үбгэн далай руу загаһа барихаар гараба.
(Старик пошёл к морю рыбу ловить.)

Түрүүшынхиеэ торхоёо хаяхадань — гансал замаг гараба.
(В первый раз закинул невод — пришла одна тина.)', 3, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fishing_2', 'Гурбадахи удаадаа торхоёо татахадань — соониинь гансахэн алтан загаһан байба!
(В третий раз вытянул невод — а там золотая рыбка!)

Она просит отпустить её человеческим голосом.', 4, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'trough_request', 'Юу хүсэнэш, үбгэн баабай?
(Что ты желаешь, старик?)

Я исполню твоё желание за свободу.', 5, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'sea_trough', 'Буса даа, байха. Тэбшэ байха.
(Ступай себе, будет. Будет вам корыто.)', 6, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'house_result', 'Үбгэн хариба. Харахадань — хуушан тэбшэ байхагүй, шэнэ тэбшэ байба.
(Вернулся старик. Глядит — старого корыта нет, новое стоит.)', 7, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'house_request', 'Тэбшэ юун бэ? Шэнэ гэр гуй!
(Корыто — что это? Проси новый дом!)

Старуха недовольна, хочет большего.', 8, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'sea_house', 'Далай долгилно.
(Море взволновалось.)

Рыбка выслушала просьбу о новом доме.', 9, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'house_result_2', 'Вместо землянки теперь высокий терем.
Үбгэн хариба — шэнэ баян гэр байба.
(Старик вернулся — новый богатый дом стоит.)', 10, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'noble_request', 'Хара хизааргүй хүгшэн байхаа болихоб, баян ноён болохоб!
(Не хочу быть простой крестьянкой, хочу быть богатой дворянкой!)

Старуха в дорогой шубе требует большего.', 11, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'sea_noble', 'Далай хара болобо.
(Море почернело.)

Рыбка молча выслушала просьбу о дворянстве.', 12, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'noble_result', 'Харихадань, хүгшэн баян ноён болоод, хажуудань слуганар зогсоно.
(Вернулся — старуха дворянкой стала, слуги вокруг стоят.)', 13, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'queen_request', 'Далайн хатан болохоб! Загаһан өөрөө намайе хүлеэжэ байг!
(Хочу быть владычицей морской! Пусть рыбка мне служит!)

Старуха хочет стать царицей моря.', 14, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'sea_storm', 'Далай дээрэ айхабтар шуурган болобо.
(На море страшная буря поднялась.)

Волны чёрные, ветер воет. Рыбка не появляется.', 15, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'sea_storm_wait', 'Үбгэн хүлеэнэ.
(Старик ждёт.)

Час ждёт, два ждёт. Рыбка не появляется.', 16, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'sea_storm_call', 'Алтан загаһан, гара! Хатан хүсэлтэй!
(Золотая рыбка, выходи! У царицы просьба!)', 17, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fish_silent', 'Рыбка ничего не ответила, уплыла вглубь. Море утихло.
Загаһан юу ч хэлээгүй, гүн рүү сэлэбэ.
(Рыбка ничего не сказала, вглубь уплыла.)', 18, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'enter_sea', 'Үбгэн далай руу орошо.
(Старик вошёл в море.)

Уһан соо гайхалтай харагдана.
(Под водой открылся удивительный мир.)', 19, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'underwater_city', 'Уһан дооро хото харагдана — шулуун гэрнүүд, шүрэн модод.
(Подводный город виден — каменные дома, коралловые деревья.)', 20, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fish_palace', 'Тэндэ Алтан загаһан ордондоо һууна.
(Там Золотая рыбка во дворце сидит.)', 21, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fish_decision', 'Үбгэн, би шамда һайн һанаатайб.
(Старик, я к тебе с добром.)

Харин хүгшэниинь хэтэршэ. Буса, гэртээ.
(Но старуха перешла границу. Иди домой.)', 22, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'final_scene', 'Үбгэн хариба.
(Вернулся старик.)

Харахадань — баян гэр байхагүй, ноён хүгшэншье үгы.
(Глядит — нет богатого дома, нет дворянки.)

Тэрэ зандаа хуушанай тэбшэ хажуудань хэбтэнэ.
(Перед ним старое корыто лежит.)', 23, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'final_talk', 'Юун болобо, үбгэн? Яагаад бидэ эндэбди?
(Что случилось, старик? Почему мы здесь?)', 24, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'epilogue_philosophy', 'Тэбшэ гээшэ хэрэггүй зүйл гү? Али хүнэй хэрэглэл?
(Корыто — ненужная вещь? Или нужная?)

Важно не то, что имеешь, а то, как ты это ценишь.

Главное в сказке — не золото, а мудрость.', 25, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'epilogue_sad', 'Сказка — ложь, да в ней намёк.
Ты прошёл «Алтан загаһан» и выучил бурятские слова!

Теперь ты знаешь, что такое ''тэбшэ'' и к чему ведёт жадность.
Сядь у корыта и подумай о вечном...', 26, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fail_lazy', 'Без труда не выловишь и рыбку из пруда.
Хүдэлмэригүйгээр загаһа барихагүйш.
(Без труда рыбу не поймаешь.)', 27, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fail_greed', 'Жадность лишила магии. Рыбка стала обычной.
Эбдэлгэ — һайн бэшэ.
(Жадность — это нехорошо.)', 28, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fail_wife_anger', 'Хүгшэн уурлажа, үбгэнэйнгээ толгай дээрэ халбагаар цохибо.
(Старуха рассердилась и ударила старика ложкой по голове.)

И выгнала тебя в тайгу.', 29, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fail_queen_wrath', 'Старуха ударила старика по щеке и отправила на конюшню.
Хүгшэн уурлажа, үбгэнэйнгээ хазаарта оруулба.
(Старуха рассердилась и старика в конюшню отправила.)', 30, NOW(), NOW());

-- choices
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue' LIMIT 1), '🌅 Эхилээд! (Начать сказку)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fishing_1' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue' LIMIT 1), '📖 Сначала посмотреть словарь', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='practice_prologue' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='practice_prologue' LIMIT 1), 'далай → море ✓', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='practice_prologue' LIMIT 1), 'далай → старик ✗', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='practice_prologue' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='practice_prologue' LIMIT 1), 'Вернуться к сказке', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue' LIMIT 1), 3);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fishing_1' LIMIT 1), '🔄 Дахин хаяха (Закинуть снова)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fishing_2' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fishing_1' LIMIT 1), '🏠 Хариха (Вернуться домой)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_lazy' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fishing_2' LIMIT 1), '🐠 Далайдаа амар мэндэ яба даа (Отпустить)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='trough_request' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fishing_2' LIMIT 1), '🔱 Забрать себе', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_greed' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='trough_request' LIMIT 1), '🏺 Тэбшэ гуйха (Попросить корыто)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_trough' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_trough' LIMIT 1), '🏠 Хариха (Вернуться домой)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_result' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_result' LIMIT 1), '🚪 Орохо (Войти в дом)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_request' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_request' LIMIT 1), '🏠 Гуйха (Просить дом)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_house' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_request' LIMIT 1), '🙅 Арсаха (Отказаться)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_wife_anger' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_house' LIMIT 1), '🏠 Ждать ответа', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_result_2' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_result_2' LIMIT 1), '🚪 Орохо (Войти)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='noble_request' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='noble_request' LIMIT 1), '🌊 Далай руу ябаха (Идти к морю)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_noble' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_noble' LIMIT 1), '🏠 Хариха (Вернуться)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='noble_result' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='noble_result' LIMIT 1), '👸 Дальше', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='queen_request' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='queen_request' LIMIT 1), '🌊 Пойти к морю', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='queen_request' LIMIT 1), '🙏 Уговорить старуху', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_queen_wrath' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm' LIMIT 1), '⏳ Хүлеэхэ (Ждать)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm_wait' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm' LIMIT 1), '📢 Дуудаха (Позвать)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm_call' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm_wait' LIMIT 1), '🏊 Сүүмхэ орохо (Войти в море)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='enter_sea' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm_wait' LIMIT 1), '🏠 Хариха (Вернуться)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_scene' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm_call' LIMIT 1), '🐠 Ждать ответа', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fish_silent' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fish_silent' LIMIT 1), '🏠 Гэртээ хариха (Идти домой)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_scene' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='enter_sea' LIMIT 1), '🏊 Үнгэнөөр ябаха (Плыть дальше)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='underwater_city' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='underwater_city' LIMIT 1), '🏰 Хотодо орохо (Войти в город)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fish_palace' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fish_palace' LIMIT 1), '🙏 Бэеэ мэндэшэлхэ (Поздороваться)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fish_decision' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fish_decision' LIMIT 1), '🏠 Бусаха (Вернуться)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_scene' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_scene' LIMIT 1), '💬 Хүгшэндэ хандаха (Подойти к старухе)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_talk' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_scene' LIMIT 1), '😢 Тэбшэдэ һууха (Сесть у корыта)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='epilogue_sad' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_talk' LIMIT 1), '📖 Хэтэбшэлһэн гээшэбди (Мы переборщили)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='epilogue_philosophy' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_lazy' LIMIT 1), '🔄 Начать заново', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_greed' LIMIT 1), '🔄 Начать заново', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_wife_anger' LIMIT 1), '🔄 Вернуться к сюжету', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_request' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_queen_wrath' LIMIT 1), '🌊 Собраться с духом и пойти', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm' LIMIT 1), 1);

-- start node reference (informational)
-- SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue';

COMMIT;
