-- Auto-generated from public/data/quest.json
START TRANSACTION;

INSERT INTO quests (code, title, description, is_active, created_at, updated_at)
VALUES ('altan_zagalan', 'Алтан загалан', 'Импортировано из public/data/quest.json', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), is_active=VALUES(is_active), updated_at=NOW();

SET @quest_id := (SELECT id FROM quests WHERE code = 'altan_zagalan' LIMIT 1);

-- optional cleanup for re-import
DELETE qc FROM quest_choices qc JOIN quest_nodes qn ON qn.id = qc.node_id WHERE qn.quest_id = @quest_id;
DELETE FROM quest_nodes WHERE quest_id = @quest_id;

-- nodes
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'prologue', 'рџЊЉ Р­СЂС‚СЌ СѓСЂРґР° СЃР°РіС‚Р° РЅСЌРіСЌ ТЇР±РіСЌРЅ С…ТЇРіС€СЌРЅ С…РѕС‘СЂ РґР°Р»Р°Р№РЅ СЌСЂСЊРµ РґСЌСЌСЂСЌ Р°Р¶Р°Т»СѓСѓРґР°Рі Р±Р°Р№РіР°Р°.

(Р”Р°РІРЅС‹Рј-РґР°РІРЅРѕ Р¶РёР»Рё-Р±С‹Р»Рё СЃС‚Р°СЂРёРє СЃРѕ СЃС‚Р°СЂСѓС…РѕР№ Сѓ СЃР°РјРѕРіРѕ СЃРёРЅРµРіРѕ РјРѕСЂСЏ.)

Р“РѕС‚РѕРІС‹ Р»Рё РІС‹ РІРѕР№С‚Рё РІ Р±СѓСЂСЏС‚СЃРєСѓСЋ СЃРєР°Р·РєСѓ В«РђР»С‚Р°РЅ Р·Р°РіР°Т»Р°РЅВ» Рё РІС‹СѓС‡РёС‚СЊ СЏР·С‹Рє?', 1, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'practice_prologue', 'рџ“ќ РџСЂР°РєС‚РёРєР°: РЎРѕРїРѕСЃС‚Р°РІСЊС‚Рµ Р±СѓСЂСЏС‚СЃРєРёРµ СЃР»РѕРІР° СЃ РёС… РїРµСЂРµРІРѕРґРѕРј.

Р’С‹Р±РµСЂРёС‚Рµ РїСЂР°РІРёР»СЊРЅС‹Р№ РІР°СЂРёР°РЅС‚:', 2, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fishing_1', 'Т®Р±РіСЌРЅ РґР°Р»Р°Р№ СЂСѓСѓ Р·Р°РіР°Т»Р° Р±Р°СЂРёС…Р°Р°СЂ РіР°СЂР°Р±Р°.
(РЎС‚Р°СЂРёРє РїРѕС€С‘Р» Рє РјРѕСЂСЋ СЂС‹Р±Сѓ Р»РѕРІРёС‚СЊ.)

РўТЇСЂТЇТЇС€С‹РЅС…РёРµСЌ С‚РѕСЂС…РѕС‘Рѕ С…Р°СЏС…Р°РґР°РЅСЊ вЂ” РіР°РЅСЃР°Р» Р·Р°РјР°Рі РіР°СЂР°Р±Р°.
(Р’ РїРµСЂРІС‹Р№ СЂР°Р· Р·Р°РєРёРЅСѓР» РЅРµРІРѕРґ вЂ” РїСЂРёС€Р»Р° РѕРґРЅР° С‚РёРЅР°.)', 3, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fishing_2', 'Р“СѓСЂР±Р°РґР°С…Рё СѓРґР°Р°РґР°Р° С‚РѕСЂС…РѕС‘Рѕ С‚Р°С‚Р°С…Р°РґР°РЅСЊ вЂ” СЃРѕРѕРЅРёРёРЅСЊ РіР°РЅСЃР°С…СЌРЅ Р°Р»С‚Р°РЅ Р·Р°РіР°Т»Р°РЅ Р±Р°Р№Р±Р°!
(Р’ С‚СЂРµС‚РёР№ СЂР°Р· РІС‹С‚СЏРЅСѓР» РЅРµРІРѕРґ вЂ” Р° С‚Р°Рј Р·РѕР»РѕС‚Р°СЏ СЂС‹Р±РєР°!)

РћРЅР° РїСЂРѕСЃРёС‚ РѕС‚РїСѓСЃС‚РёС‚СЊ РµС‘ С‡РµР»РѕРІРµС‡РµСЃРєРёРј РіРѕР»РѕСЃРѕРј.', 4, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'trough_request', 'Р®Сѓ С…ТЇСЃСЌРЅСЌС€, ТЇР±РіСЌРЅ Р±Р°Р°Р±Р°Р№?
(Р§С‚Рѕ С‚С‹ Р¶РµР»Р°РµС€СЊ, СЃС‚Р°СЂРёРє?)

РЇ РёСЃРїРѕР»РЅСЋ С‚РІРѕС‘ Р¶РµР»Р°РЅРёРµ Р·Р° СЃРІРѕР±РѕРґСѓ.', 5, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'sea_trough', 'Р‘СѓСЃР° РґР°Р°, Р±Р°Р№С…Р°. РўСЌР±С€СЌ Р±Р°Р№С…Р°.
(РЎС‚СѓРїР°Р№ СЃРµР±Рµ, Р±СѓРґРµС‚. Р‘СѓРґРµС‚ РІР°Рј РєРѕСЂС‹С‚Рѕ.)', 6, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'house_result', 'Т®Р±РіСЌРЅ С…Р°СЂРёР±Р°. РҐР°СЂР°С…Р°РґР°РЅСЊ вЂ” С…СѓСѓС€Р°РЅ С‚СЌР±С€СЌ Р±Р°Р№С…Р°РіТЇР№, С€СЌРЅСЌ С‚СЌР±С€СЌ Р±Р°Р№Р±Р°.
(Р’РµСЂРЅСѓР»СЃСЏ СЃС‚Р°СЂРёРє. Р“Р»СЏРґРёС‚ вЂ” СЃС‚Р°СЂРѕРіРѕ РєРѕСЂС‹С‚Р° РЅРµС‚, РЅРѕРІРѕРµ СЃС‚РѕРёС‚.)', 7, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'house_request', 'РўСЌР±С€СЌ СЋСѓРЅ Р±СЌ? РЁСЌРЅСЌ РіСЌСЂ РіСѓР№!
(РљРѕСЂС‹С‚Рѕ вЂ” С‡С‚Рѕ СЌС‚Рѕ? РџСЂРѕСЃРё РЅРѕРІС‹Р№ РґРѕРј!)

РЎС‚Р°СЂСѓС…Р° РЅРµРґРѕРІРѕР»СЊРЅР°, С…РѕС‡РµС‚ Р±РѕР»СЊС€РµРіРѕ.', 8, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'sea_house', 'Р”Р°Р»Р°Р№ РґРѕР»РіРёР»РЅРѕ.
(РњРѕСЂРµ РІР·РІРѕР»РЅРѕРІР°Р»РѕСЃСЊ.)

Р С‹Р±РєР° РІС‹СЃР»СѓС€Р°Р»Р° РїСЂРѕСЃСЊР±Сѓ Рѕ РЅРѕРІРѕРј РґРѕРјРµ.', 9, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'house_result_2', 'Р’РјРµСЃС‚Рѕ Р·РµРјР»СЏРЅРєРё С‚РµРїРµСЂСЊ РІС‹СЃРѕРєРёР№ С‚РµСЂРµРј.
Т®Р±РіСЌРЅ С…Р°СЂРёР±Р° вЂ” С€СЌРЅСЌ Р±Р°СЏРЅ РіСЌСЂ Р±Р°Р№Р±Р°.
(РЎС‚Р°СЂРёРє РІРµСЂРЅСѓР»СЃСЏ вЂ” РЅРѕРІС‹Р№ Р±РѕРіР°С‚С‹Р№ РґРѕРј СЃС‚РѕРёС‚.)', 10, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'noble_request', 'РҐР°СЂР° С…РёР·Р°Р°СЂРіТЇР№ С…ТЇРіС€СЌРЅ Р±Р°Р№С…Р°Р° Р±РѕР»РёС…РѕР±, Р±Р°СЏРЅ РЅРѕС‘РЅ Р±РѕР»РѕС…РѕР±!
(РќРµ С…РѕС‡Сѓ Р±С‹С‚СЊ РїСЂРѕСЃС‚РѕР№ РєСЂРµСЃС‚СЊСЏРЅРєРѕР№, С…РѕС‡Сѓ Р±С‹С‚СЊ Р±РѕРіР°С‚РѕР№ РґРІРѕСЂСЏРЅРєРѕР№!)

РЎС‚Р°СЂСѓС…Р° РІ РґРѕСЂРѕРіРѕР№ С€СѓР±Рµ С‚СЂРµР±СѓРµС‚ Р±РѕР»СЊС€РµРіРѕ.', 11, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'sea_noble', 'Р”Р°Р»Р°Р№ С…Р°СЂР° Р±РѕР»РѕР±Рѕ.
(РњРѕСЂРµ РїРѕС‡РµСЂРЅРµР»Рѕ.)

Р С‹Р±РєР° РјРѕР»С‡Р° РІС‹СЃР»СѓС€Р°Р»Р° РїСЂРѕСЃСЊР±Сѓ Рѕ РґРІРѕСЂСЏРЅСЃС‚РІРµ.', 12, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'noble_result', 'РҐР°СЂРёС…Р°РґР°РЅСЊ, С…ТЇРіС€СЌРЅ Р±Р°СЏРЅ РЅРѕС‘РЅ Р±РѕР»РѕРѕРґ, С…Р°Р¶СѓСѓРґР°РЅСЊ СЃР»СѓРіР°РЅР°СЂ Р·РѕРіСЃРѕРЅРѕ.
(Р’РµСЂРЅСѓР»СЃСЏ вЂ” СЃС‚Р°СЂСѓС…Р° РґРІРѕСЂСЏРЅРєРѕР№ СЃС‚Р°Р»Р°, СЃР»СѓРіРё РІРѕРєСЂСѓРі СЃС‚РѕСЏС‚.)', 13, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'queen_request', 'Р”Р°Р»Р°Р№РЅ С…Р°С‚Р°РЅ Р±РѕР»РѕС…РѕР±! Р—Р°РіР°Т»Р°РЅ У©У©СЂУ©У© РЅР°РјР°Р№Рµ С…ТЇР»РµСЌР¶СЌ Р±Р°Р№Рі!
(РҐРѕС‡Сѓ Р±С‹С‚СЊ РІР»Р°РґС‹С‡РёС†РµР№ РјРѕСЂСЃРєРѕР№! РџСѓСЃС‚СЊ СЂС‹Р±РєР° РјРЅРµ СЃР»СѓР¶РёС‚!)

РЎС‚Р°СЂСѓС…Р° С…РѕС‡РµС‚ СЃС‚Р°С‚СЊ С†Р°СЂРёС†РµР№ РјРѕСЂСЏ.', 14, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'sea_storm', 'Р”Р°Р»Р°Р№ РґСЌСЌСЂСЌ Р°Р№С…Р°Р±С‚Р°СЂ С€СѓСѓСЂРіР°РЅ Р±РѕР»РѕР±Рѕ.
(РќР° РјРѕСЂРµ СЃС‚СЂР°С€РЅР°СЏ Р±СѓСЂСЏ РїРѕРґРЅСЏР»Р°СЃСЊ.)

Р’РѕР»РЅС‹ С‡С‘СЂРЅС‹Рµ, РІРµС‚РµСЂ РІРѕРµС‚. Р С‹Р±РєР° РЅРµ РїРѕСЏРІР»СЏРµС‚СЃСЏ.', 15, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'sea_storm_wait', 'Т®Р±РіСЌРЅ С…ТЇР»РµСЌРЅСЌ.
(РЎС‚Р°СЂРёРє Р¶РґС‘С‚.)

Р§Р°СЃ Р¶РґС‘С‚, РґРІР° Р¶РґС‘С‚. Р С‹Р±РєР° РЅРµ РїРѕСЏРІР»СЏРµС‚СЃСЏ.', 16, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'sea_storm_call', 'РђР»С‚Р°РЅ Р·Р°РіР°Т»Р°РЅ, РіР°СЂР°! РҐР°С‚Р°РЅ С…ТЇСЃСЌР»С‚СЌР№!
(Р—РѕР»РѕС‚Р°СЏ СЂС‹Р±РєР°, РІС‹С…РѕРґРё! РЈ С†Р°СЂРёС†С‹ РїСЂРѕСЃСЊР±Р°!)', 17, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fish_silent', 'Р С‹Р±РєР° РЅРёС‡РµРіРѕ РЅРµ РѕС‚РІРµС‚РёР»Р°, СѓРїР»С‹Р»Р° РІРіР»СѓР±СЊ. РњРѕСЂРµ СѓС‚РёС…Р»Рѕ.
Р—Р°РіР°Т»Р°РЅ СЋСѓ С‡ С…СЌР»СЌСЌРіТЇР№, РіТЇРЅ СЂТЇТЇ СЃСЌР»СЌР±СЌ.
(Р С‹Р±РєР° РЅРёС‡РµРіРѕ РЅРµ СЃРєР°Р·Р°Р»Р°, РІРіР»СѓР±СЊ СѓРїР»С‹Р»Р°.)', 18, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'enter_sea', 'Т®Р±РіСЌРЅ РґР°Р»Р°Р№ СЂСѓСѓ РѕСЂРѕС€Рѕ.
(РЎС‚Р°СЂРёРє РІРѕС€С‘Р» РІ РјРѕСЂРµ.)

РЈТ»Р°РЅ СЃРѕРѕ РіР°Р№С…Р°Р»С‚Р°Р№ С…Р°СЂР°РіРґР°РЅР°.
(РџРѕРґ РІРѕРґРѕР№ РѕС‚РєСЂС‹Р»СЃСЏ СѓРґРёРІРёС‚РµР»СЊРЅС‹Р№ РјРёСЂ.)', 19, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'underwater_city', 'РЈТ»Р°РЅ РґРѕРѕСЂРѕ С…РѕС‚Рѕ С…Р°СЂР°РіРґР°РЅР° вЂ” С€СѓР»СѓСѓРЅ РіСЌСЂРЅТЇТЇРґ, С€ТЇСЂСЌРЅ РјРѕРґРѕРґ.
(РџРѕРґРІРѕРґРЅС‹Р№ РіРѕСЂРѕРґ РІРёРґРµРЅ вЂ” РєР°РјРµРЅРЅС‹Рµ РґРѕРјР°, РєРѕСЂР°Р»Р»РѕРІС‹Рµ РґРµСЂРµРІСЊСЏ.)', 20, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fish_palace', 'РўСЌРЅРґСЌ РђР»С‚Р°РЅ Р·Р°РіР°Т»Р°РЅ РѕСЂРґРѕРЅРґРѕРѕ Т»СѓСѓРЅР°.
(РўР°Рј Р—РѕР»РѕС‚Р°СЏ СЂС‹Р±РєР° РІРѕ РґРІРѕСЂС†Рµ СЃРёРґРёС‚.)', 21, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fish_decision', 'Т®Р±РіСЌРЅ, Р±Рё С€Р°РјРґР° Т»Р°Р№РЅ Т»Р°РЅР°Р°С‚Р°Р№Р±.
(РЎС‚Р°СЂРёРє, СЏ Рє С‚РµР±Рµ СЃ РґРѕР±СЂРѕРј.)

РҐР°СЂРёРЅ С…ТЇРіС€СЌРЅРёРёРЅСЊ С…СЌС‚СЌСЂС€СЌ. Р‘СѓСЃР°, РіСЌСЂС‚СЌСЌ.
(РќРѕ СЃС‚Р°СЂСѓС…Р° РїРµСЂРµС€Р»Р° РіСЂР°РЅРёС†Сѓ. РРґРё РґРѕРјРѕР№.)', 22, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'final_scene', 'Т®Р±РіСЌРЅ С…Р°СЂРёР±Р°.
(Р’РµСЂРЅСѓР»СЃСЏ СЃС‚Р°СЂРёРє.)

РҐР°СЂР°С…Р°РґР°РЅСЊ вЂ” Р±Р°СЏРЅ РіСЌСЂ Р±Р°Р№С…Р°РіТЇР№, РЅРѕС‘РЅ С…ТЇРіС€СЌРЅС€СЊРµ ТЇРіС‹.
(Р“Р»СЏРґРёС‚ вЂ” РЅРµС‚ Р±РѕРіР°С‚РѕРіРѕ РґРѕРјР°, РЅРµС‚ РґРІРѕСЂСЏРЅРєРё.)

РўСЌСЂСЌ Р·Р°РЅРґР°Р° С…СѓСѓС€Р°РЅР°Р№ С‚СЌР±С€СЌ С…Р°Р¶СѓСѓРґР°РЅСЊ С…СЌР±С‚СЌРЅСЌ.
(РџРµСЂРµРґ РЅРёРј СЃС‚Р°СЂРѕРµ РєРѕСЂС‹С‚Рѕ Р»РµР¶РёС‚.)', 23, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'final_talk', 'Р®СѓРЅ Р±РѕР»РѕР±Рѕ, ТЇР±РіСЌРЅ? РЇР°РіР°Р°Рґ Р±РёРґСЌ СЌРЅРґСЌР±РґРё?
(Р§С‚Рѕ СЃР»СѓС‡РёР»РѕСЃСЊ, СЃС‚Р°СЂРёРє? РџРѕС‡РµРјСѓ РјС‹ Р·РґРµСЃСЊ?)', 24, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'epilogue_philosophy', 'РўСЌР±С€СЌ РіСЌСЌС€СЌ С…СЌСЂСЌРіРіТЇР№ Р·ТЇР№Р» РіТЇ? РђР»Рё С…ТЇРЅСЌР№ С…СЌСЂСЌРіР»СЌР»?
(РљРѕСЂС‹С‚Рѕ вЂ” РЅРµРЅСѓР¶РЅР°СЏ РІРµС‰СЊ? РР»Рё РЅСѓР¶РЅР°СЏ?)

Р’Р°Р¶РЅРѕ РЅРµ С‚Рѕ, С‡С‚Рѕ РёРјРµРµС€СЊ, Р° С‚Рѕ, РєР°Рє С‚С‹ СЌС‚Рѕ С†РµРЅРёС€СЊ.

Р“Р»Р°РІРЅРѕРµ РІ СЃРєР°Р·РєРµ вЂ” РЅРµ Р·РѕР»РѕС‚Рѕ, Р° РјСѓРґСЂРѕСЃС‚СЊ.', 25, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'epilogue_sad', 'РЎРєР°Р·РєР° вЂ” Р»РѕР¶СЊ, РґР° РІ РЅРµР№ РЅР°РјС‘Рє.
РўС‹ РїСЂРѕС€С‘Р» В«РђР»С‚Р°РЅ Р·Р°РіР°Т»Р°РЅВ» Рё РІС‹СѓС‡РёР» Р±СѓСЂСЏС‚СЃРєРёРµ СЃР»РѕРІР°!

РўРµРїРµСЂСЊ С‚С‹ Р·РЅР°РµС€СЊ, С‡С‚Рѕ С‚Р°РєРѕРµ ''С‚СЌР±С€СЌ'' Рё Рє С‡РµРјСѓ РІРµРґС‘С‚ Р¶Р°РґРЅРѕСЃС‚СЊ.
РЎСЏРґСЊ Сѓ РєРѕСЂС‹С‚Р° Рё РїРѕРґСѓРјР°Р№ Рѕ РІРµС‡РЅРѕРј...', 26, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fail_lazy', 'Р‘РµР· С‚СЂСѓРґР° РЅРµ РІС‹Р»РѕРІРёС€СЊ Рё СЂС‹Р±РєСѓ РёР· РїСЂСѓРґР°.
РҐТЇРґСЌР»РјСЌСЂРёРіТЇР№РіСЌСЌСЂ Р·Р°РіР°Т»Р° Р±Р°СЂРёС…Р°РіТЇР№С€.
(Р‘РµР· С‚СЂСѓРґР° СЂС‹Р±Сѓ РЅРµ РїРѕР№РјР°РµС€СЊ.)', 27, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fail_greed', 'Р–Р°РґРЅРѕСЃС‚СЊ Р»РёС€РёР»Р° РјР°РіРёРё. Р С‹Р±РєР° СЃС‚Р°Р»Р° РѕР±С‹С‡РЅРѕР№.
Р­Р±РґСЌР»РіСЌ вЂ” Т»Р°Р№РЅ Р±СЌС€СЌ.
(Р–Р°РґРЅРѕСЃС‚СЊ вЂ” СЌС‚Рѕ РЅРµС…РѕСЂРѕС€Рѕ.)', 28, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fail_wife_anger', 'РҐТЇРіС€СЌРЅ СѓСѓСЂР»Р°Р¶Р°, ТЇР±РіСЌРЅСЌР№РЅРіСЌСЌ С‚РѕР»РіР°Р№ РґСЌСЌСЂСЌ С…Р°Р»Р±Р°РіР°Р°СЂ С†РѕС…РёР±Рѕ.
(РЎС‚Р°СЂСѓС…Р° СЂР°СЃСЃРµСЂРґРёР»Р°СЃСЊ Рё СѓРґР°СЂРёР»Р° СЃС‚Р°СЂРёРєР° Р»РѕР¶РєРѕР№ РїРѕ РіРѕР»РѕРІРµ.)

Р РІС‹РіРЅР°Р»Р° С‚РµР±СЏ РІ С‚Р°Р№РіСѓ.', 29, NOW(), NOW());
INSERT INTO quest_nodes (quest_id, node_key, body, order_num, created_at, updated_at) VALUES (@quest_id, 'fail_queen_wrath', 'РЎС‚Р°СЂСѓС…Р° СѓРґР°СЂРёР»Р° СЃС‚Р°СЂРёРєР° РїРѕ С‰РµРєРµ Рё РѕС‚РїСЂР°РІРёР»Р° РЅР° РєРѕРЅСЋС€РЅСЋ.
РҐТЇРіС€СЌРЅ СѓСѓСЂР»Р°Р¶Р°, ТЇР±РіСЌРЅСЌР№РЅРіСЌСЌ С…Р°Р·Р°Р°СЂС‚Р° РѕСЂСѓСѓР»Р±Р°.
(РЎС‚Р°СЂСѓС…Р° СЂР°СЃСЃРµСЂРґРёР»Р°СЃСЊ Рё СЃС‚Р°СЂРёРєР° РІ РєРѕРЅСЋС€РЅСЋ РѕС‚РїСЂР°РІРёР»Р°.)', 30, NOW(), NOW());

-- choices
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue' LIMIT 1), 'рџЊ… Р­С…РёР»СЌСЌРґ! (РќР°С‡Р°С‚СЊ СЃРєР°Р·РєСѓ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fishing_1' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue' LIMIT 1), 'рџ“– РЎРЅР°С‡Р°Р»Р° РїРѕСЃРјРѕС‚СЂРµС‚СЊ СЃР»РѕРІР°СЂСЊ', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='practice_prologue' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='practice_prologue' LIMIT 1), 'РґР°Р»Р°Р№ в†’ РјРѕСЂРµ вњ“', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='practice_prologue' LIMIT 1), 'РґР°Р»Р°Р№ в†’ СЃС‚Р°СЂРёРє вњ—', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='practice_prologue' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='practice_prologue' LIMIT 1), 'Р’РµСЂРЅСѓС‚СЊСЃСЏ Рє СЃРєР°Р·РєРµ', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue' LIMIT 1), 3);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fishing_1' LIMIT 1), 'рџ”„ Р”Р°С…РёРЅ С…Р°СЏС…Р° (Р—Р°РєРёРЅСѓС‚СЊ СЃРЅРѕРІР°)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fishing_2' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fishing_1' LIMIT 1), 'рџЏ  РҐР°СЂРёС…Р° (Р’РµСЂРЅСѓС‚СЊСЃСЏ РґРѕРјРѕР№)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_lazy' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fishing_2' LIMIT 1), 'рџђ  Р”Р°Р»Р°Р№РґР°Р° Р°РјР°СЂ РјСЌРЅРґСЌ СЏР±Р° РґР°Р° (РћС‚РїСѓСЃС‚РёС‚СЊ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='trough_request' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fishing_2' LIMIT 1), 'рџ”± Р—Р°Р±СЂР°С‚СЊ СЃРµР±Рµ', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_greed' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='trough_request' LIMIT 1), 'рџЏє РўСЌР±С€СЌ РіСѓР№С…Р° (РџРѕРїСЂРѕСЃРёС‚СЊ РєРѕСЂС‹С‚Рѕ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_trough' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_trough' LIMIT 1), 'рџЏ  РҐР°СЂРёС…Р° (Р’РµСЂРЅСѓС‚СЊСЃСЏ РґРѕРјРѕР№)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_result' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_result' LIMIT 1), 'рџљЄ РћСЂРѕС…Рѕ (Р’РѕР№С‚Рё РІ РґРѕРј)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_request' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_request' LIMIT 1), 'рџЏ  Р“СѓР№С…Р° (РџСЂРѕСЃРёС‚СЊ РґРѕРј)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_house' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_request' LIMIT 1), 'рџ™… РђСЂСЃР°С…Р° (РћС‚РєР°Р·Р°С‚СЊСЃСЏ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_wife_anger' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_house' LIMIT 1), 'рџЏ  Р–РґР°С‚СЊ РѕС‚РІРµС‚Р°', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_result_2' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_result_2' LIMIT 1), 'рџљЄ РћСЂРѕС…Рѕ (Р’РѕР№С‚Рё)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='noble_request' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='noble_request' LIMIT 1), 'рџЊЉ Р”Р°Р»Р°Р№ СЂСѓСѓ СЏР±Р°С…Р° (РРґС‚Рё Рє РјРѕСЂСЋ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_noble' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_noble' LIMIT 1), 'рџЏ  РҐР°СЂРёС…Р° (Р’РµСЂРЅСѓС‚СЊСЃСЏ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='noble_result' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='noble_result' LIMIT 1), 'рџ‘ё Р”Р°Р»СЊС€Рµ', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='queen_request' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='queen_request' LIMIT 1), 'рџЊЉ РџРѕР№С‚Рё Рє РјРѕСЂСЋ', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='queen_request' LIMIT 1), 'рџ™Џ РЈРіРѕРІРѕСЂРёС‚СЊ СЃС‚Р°СЂСѓС…Сѓ', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_queen_wrath' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm' LIMIT 1), 'вЏі РҐТЇР»РµСЌС…СЌ (Р–РґР°С‚СЊ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm_wait' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm' LIMIT 1), 'рџ“ў Р”СѓСѓРґР°С…Р° (РџРѕР·РІР°С‚СЊ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm_call' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm_wait' LIMIT 1), 'рџЏЉ РЎТЇТЇРјС…СЌ РѕСЂРѕС…Рѕ (Р’РѕР№С‚Рё РІ РјРѕСЂРµ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='enter_sea' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm_wait' LIMIT 1), 'рџЏ  РҐР°СЂРёС…Р° (Р’РµСЂРЅСѓС‚СЊСЃСЏ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_scene' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm_call' LIMIT 1), 'рџђ  Р–РґР°С‚СЊ РѕС‚РІРµС‚Р°', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fish_silent' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fish_silent' LIMIT 1), 'рџЏ  Р“СЌСЂС‚СЌСЌ С…Р°СЂРёС…Р° (РРґС‚Рё РґРѕРјРѕР№)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_scene' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='enter_sea' LIMIT 1), 'рџЏЉ Т®РЅРіСЌРЅУ©У©СЂ СЏР±Р°С…Р° (РџР»С‹С‚СЊ РґР°Р»СЊС€Рµ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='underwater_city' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='underwater_city' LIMIT 1), 'рџЏ° РҐРѕС‚РѕРґРѕ РѕСЂРѕС…Рѕ (Р’РѕР№С‚Рё РІ РіРѕСЂРѕРґ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fish_palace' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fish_palace' LIMIT 1), 'рџ™Џ Р‘СЌРµСЌ РјСЌРЅРґСЌС€СЌР»С…СЌ (РџРѕР·РґРѕСЂРѕРІР°С‚СЊСЃСЏ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fish_decision' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fish_decision' LIMIT 1), 'рџЏ  Р‘СѓСЃР°С…Р° (Р’РµСЂРЅСѓС‚СЊСЃСЏ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_scene' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_scene' LIMIT 1), 'рџ’¬ РҐТЇРіС€СЌРЅРґСЌ С…Р°РЅРґР°С…Р° (РџРѕРґРѕР№С‚Рё Рє СЃС‚Р°СЂСѓС…Рµ)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_talk' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_scene' LIMIT 1), 'рџў РўСЌР±С€СЌРґСЌ Т»СѓСѓС…Р° (РЎРµСЃС‚СЊ Сѓ РєРѕСЂС‹С‚Р°)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='epilogue_sad' LIMIT 1), 2);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='final_talk' LIMIT 1), 'рџ“– РҐСЌС‚СЌР±С€СЌР»Т»СЌРЅ РіСЌСЌС€СЌР±РґРё (РњС‹ РїРµСЂРµР±РѕСЂС‰РёР»Рё)', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='epilogue_philosophy' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_lazy' LIMIT 1), 'рџ”„ РќР°С‡Р°С‚СЊ Р·Р°РЅРѕРІРѕ', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_greed' LIMIT 1), 'рџ”„ РќР°С‡Р°С‚СЊ Р·Р°РЅРѕРІРѕ', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_wife_anger' LIMIT 1), 'рџ”„ Р’РµСЂРЅСѓС‚СЊСЃСЏ Рє СЃСЋР¶РµС‚Сѓ', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='house_request' LIMIT 1), 1);
INSERT INTO quest_choices (node_id, choice_text, next_node_id, order_num) VALUES ((SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='fail_queen_wrath' LIMIT 1), 'рџЊЉ РЎРѕР±СЂР°С‚СЊСЃСЏ СЃ РґСѓС…РѕРј Рё РїРѕР№С‚Рё', (SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='sea_storm' LIMIT 1), 1);

-- start node reference (informational)
-- SELECT id FROM quest_nodes WHERE quest_id=@quest_id AND node_key='prologue';

COMMIT;
