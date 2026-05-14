            <div class="teacher-grid">
                <div class="teacher-card teacher-hidden-panel" aria-hidden="true">
                    <h3 class="teacher-heading-row">
                        Единый словарь
                        <button type="button" data-action="open-dictionary-modal" class="btn btn-success btn-sm">+ Добавить слово</button>
                    </h3>
                    <div class="table-scroll">
                        <table class="teacher-table">
                            <tr class="teacher-table-head-row">
                                <th class="teacher-table-th">Бурятское слово</th>
                                <th class="teacher-table-th">Перевод</th>
                                <th class="teacher-table-th teacher-table-th--narrow"></th>
                            </tr>
                            @foreach($dictionary as $index => $word)
                                <tr class="teacher-table-row">
                                    <td class="teacher-table-td teacher-table-td--compact">{{ $word['word'] }}</td>
                                    <td class="teacher-table-td teacher-table-td--compact">{{ $word['translation'] }}</td>
                                    <td class="teacher-table-td teacher-table-td--compact">
                                        <button type="button" data-action="delete-word" data-entry-id="{{ $word['id'] ?? 0 }}" class="teacher-btn-danger-xs">Удалить</button>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>

                <div class="teacher-card teacher-editor-card">
                    <h3 class="teacher-heading-row">
                        Редактор
                        <a href="{{ route('teacher.courses.page') }}" class="btn btn-outline btn-sm">Мои курсы</a>
                    </h3>

                    <div class="teacher-stage teacher-field">
                        <div class="teacher-stage-head">
                            <div>
                                <div class="teacher-stage-kicker">Шаг 1</div>
                                <h4 class="teacher-stage-title">Курс</h4>
                            </div>
                            <button id="createCourseBtn" type="button" data-action="create-course" class="btn btn-success btn-sm">+ Создать курс</button>
                        </div>
                        <div id="courseStageSummary" class="teacher-stage-summary teacher-muted"></div>
                        <form action="{{ route('teacher.indes.handle') }}" method="POST" class="teacher-inline-input teacher-fallback-form">
                            @csrf
                            <input type="hidden" name="action" value="create_course">
                            <input type="text" name="title" class="teacher-input" placeholder="Название нового курса" required>
                            <button type="submit" class="btn btn-success btn-sm">Создать</button>
                        </form>
                    </div>

                    <div class="teacher-field">
                        <label for="courseSelector" class="teacher-label">Курс:</label>
                        <select id="courseSelector" data-change-action="sync-module-selector" class="teacher-select">
                            <option value="">Сначала выберите или создайте курс</option>
                            @foreach(($curriculum['courses'] ?? []) as $course)
                                <option value="{{ $course['id'] }}">{{ $course['title'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="moduleStage" class="teacher-stage teacher-field">
                        <div class="teacher-stage-head">
                            <div>
                                <div class="teacher-stage-kicker">Шаг 2</div>
                                <h4 class="teacher-stage-title">Модуль</h4>
                            </div>
                            <button id="createModuleBtn" type="button" data-action="create-module" class="btn btn-accent btn-sm">+ Создать модуль</button>
                        </div>
                        <div id="moduleStageSummary" class="teacher-stage-summary teacher-muted"></div>
                        <form action="{{ route('teacher.indes.handle') }}" method="POST" class="teacher-inline-input teacher-fallback-form">
                            @csrf
                            <input type="hidden" name="action" value="create_module">
                            <input type="hidden" id="moduleCourseIdInput" name="course_id" value="">
                            <input type="text" name="title" class="teacher-input" placeholder="Название нового модуля" required>
                            <button type="submit" class="btn btn-accent btn-sm">Создать</button>
                        </form>

                        <label for="lessonModule" class="teacher-label">Модуль:</label>
                        <select id="lessonModule" data-change-action="sync-lesson-selector" class="teacher-select">
                        </select>
                    </div>

                    <div id="lessonStage" class="teacher-stage teacher-field">
                        <div class="teacher-stage-head">
                            <div>
                                <div class="teacher-stage-kicker">Шаг 3</div>
                                <h4 class="teacher-stage-title">Урок</h4>
                            </div>
                            <button id="createLessonBtn" type="button" data-action="open-lesson-modal" class="btn btn-primary btn-sm">+ Новый урок</button>
                        </div>
                        <div id="lessonStageSummary" class="teacher-stage-summary teacher-muted"></div>
                        <form action="{{ route('teacher.indes.handle') }}" method="POST" class="teacher-inline-input teacher-fallback-form">
                            @csrf
                            <input type="hidden" name="action" value="create_lesson">
                            <input type="hidden" id="lessonCourseIdInput" name="course_id" value="">
                            <input type="hidden" id="lessonModuleIdInput" name="module_id" value="">
                            <input type="text" name="title" class="teacher-input" placeholder="Название нового урока" required>
                            <button type="submit" class="btn btn-primary btn-sm">Создать</button>
                        </form>

                        <select id="lessonSelector" data-change-action="load-lesson" class="teacher-select">
                            <option value="">Выберите урок для редактирования</option>
                            @foreach($lessons as $id => $lesson)
                                @php($courseTitle = $courseTitleMap[(int) ($lesson['course_id'] ?? 0)] ?? 'Без курса')
                                @php($moduleTitle = $moduleTitleMap[(int) ($lesson['module_id'] ?? 0)] ?? 'Без модуля')
                                <option value="{{ $id }}" data-course-id="{{ $lesson['course_id'] ?? '' }}" data-module-id="{{ $lesson['module_id'] ?? '' }}">{{ $lesson['title'] }} · {{ $courseTitle }} / {{ $moduleTitle }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="lessonEditor" class="is-hidden">
                        <div class="teacher-field">
                            <label for="lessonTitle" class="teacher-label">Название урока:</label>
                            <input type="text" id="lessonTitle" class="teacher-input">
                        </div>

                        <div class="teacher-field">
                            <label class="teacher-label">Структура урока:</label>
                            <div class="teacher-segment">
                                <div class="teacher-segment-head">
                                    <div>
                                        <h4 class="teacher-segment-title">Теория</h4>
                                    </div>
                                    <button type="button" data-action="add-theory-step" class="btn btn-success btn-sm">+ Добавить теорию</button>
                                </div>
                                <div id="theoryStepsList" class="teacher-steps-list"></div>
                            </div>
                            <div class="teacher-segment">
                                <div class="teacher-segment-head">
                                    <div>
                                        <h4 class="teacher-segment-title">Практика</h4>
                                    </div>
                                    <button type="button" data-action="add-practice-step" class="btn btn-success btn-sm">+ Добавить практику</button>
                                </div>
                                <div id="practiceStepsList" class="teacher-steps-list"></div>
                            </div>
                        </div>

                        <div class="teacher-actions">
                            <button type="button" data-action="save-lesson" class="btn btn-primary">Сохранить урок</button>
                            <button type="button" data-action="preview-lesson" class="btn btn-accent is-hidden">Предпросмотр</button>
                            <button type="button" data-action="delete-lesson" class="btn btn-danger">Удалить урок</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="dictionaryModal" class="teacher-modal">
                <div class="teacher-modal-card teacher-modal-card--dictionary">
                    <h3 class="teacher-section-title">Добавить слово в словарь</h3>
                    <form id="addWordForm">
                        <input type="text" id="newWord" placeholder="Бурятское слово" class="teacher-input teacher-field">
                        <input type="text" id="newTranslation" placeholder="Перевод" class="teacher-input teacher-field">
                        <div class="teacher-modal-actions teacher-modal-actions--end">
                            <button type="button" data-action="close-dictionary-modal" class="btn btn-muted btn-sm">Отмена</button>
                            <button type="submit" class="btn btn-success btn-sm">Добавить</button>
                        </div>
                    </form>
                </div>
            </div>
            <div id="uiInputModal" class="teacher-modal">
                <div class="teacher-modal-card teacher-modal-card--compact">
                    <h3 id="uiInputTitle" class="teacher-section-title">Новый элемент</h3>
                    <p id="uiInputDescription" class="teacher-muted teacher-field teacher-neg-top"></p>
                    <label id="uiInputLabel" for="uiInputField" class="teacher-label">Название</label>
                    <input type="text" id="uiInputField" class="teacher-input teacher-field">
                    <div id="uiInputError" class="teacher-modal-error is-hidden"></div>
                    <div class="teacher-modal-actions teacher-modal-actions--end">
                        <button type="button" data-action="close-ui-input-modal" class="btn btn-muted btn-sm">Отмена</button>
                        <button type="button" id="uiInputSubmit" class="btn btn-success btn-sm">Сохранить</button>
                    </div>
                </div>
            </div>
            <div id="confirmModal" class="teacher-modal">
                <div class="teacher-modal-card teacher-modal-card--compact">
                    <h3 id="confirmTitle" class="teacher-section-title">Подтверждение</h3>
                    <p id="confirmDescription" class="teacher-muted teacher-field"></p>
                    <div class="teacher-modal-actions teacher-modal-actions--end">
                        <button type="button" data-action="close-confirm-modal" class="btn btn-muted btn-sm">Отмена</button>
                        <button type="button" id="confirmSubmit" class="btn btn-danger btn-sm">Подтвердить</button>
                    </div>
                </div>
            </div>
            <div id="teacherToast" class="teacher-toast is-hidden"></div>
            <div id="stepModal" class="teacher-modal">
                <div class="teacher-modal-card teacher-modal-card--step">
                    <h3 class="teacher-section-title">Настроить шаг</h3>
                    <select id="stepType" data-change-action="toggle-step-fields" class="teacher-select teacher-field">
                        <option value="theory">Теоретический блок</option>
                        <option value="multiple_choice">Тест с вариантами</option>
                        <option value="fill_blanks">Пропуск в предложении</option>
                        <option value="matching">Сопоставление пар</option>
                        <option value="word_order">Сборка фразы</option>
                        <option value="audio_pick">Аудио выбор</option>
                        <option value="flashcards">Флеш-карта</option>
                    </select>

                    <div id="theoryFields">
                        <label class="teacher-label">Заголовок блока:</label>
                        <input type="text" id="theoryTitle" class="teacher-input teacher-field">
                        <label class="teacher-label">Текст теории:</label>
                        <div class="teacher-toolbar-wrap">
                            <div class="teacher-toolbar">
                                <button type="button" data-action="apply-text-format" data-value="bold" class="wysiwyg-btn">B</button>
                                <button type="button" data-action="apply-text-format" data-value="italic" class="wysiwyg-btn"><i>I</i></button>
                                <button type="button" data-action="apply-text-format" data-value="underline" class="wysiwyg-btn"><u>U</u></button>
                                <button type="button" data-action="apply-block-format" data-value="p" class="wysiwyg-btn">P</button>
                                <button type="button" data-action="apply-block-format" data-value="h2" class="wysiwyg-btn">H2</button>
                                <button type="button" data-action="apply-block-format" data-value="h3" class="wysiwyg-btn">H3</button>
                                <button type="button" data-action="apply-text-format" data-value="insertUnorderedList" class="wysiwyg-btn">UL</button>
                                <button type="button" data-action="apply-text-format" data-value="insertOrderedList" class="wysiwyg-btn">OL</button>
                                <button type="button" data-action="insert-editor-link" class="wysiwyg-btn">Link</button>
                                <button type="button" data-action="clear-editor-formatting" class="wysiwyg-btn">Clear</button>
                            </div>
                            <div id="wysiwygEditor" contenteditable="true" class="teacher-editor-surface"></div>
                        </div>
                    </div>

                    <div id="taskSourceFields" class="is-hidden">
                        <label class="teacher-label">Режим составления задания:</label>
                        <select id="contentSourceMode" data-change-action="toggle-source-mode-fields" class="teacher-select teacher-field">
                            <option value="manual">Ручной</option>
                            <option value="dictionary">Из словаря</option>
                        </select>
                    </div>

                    <div id="dictionaryAssistFields" class="is-hidden">
                        <label class="teacher-label">Поиск по словарю:</label>
                        <input type="text" id="dictionarySearchInput" placeholder="Начни вводить слово или перевод" data-input-action="filter-dictionary-options" class="teacher-input teacher-field">
                        <label class="teacher-label">Слова из словаря:</label>
                        <select id="dictionaryWordSelector" class="teacher-select teacher-field" multiple size="6"></select>
                        <div id="dictionarySearchMeta" class="teacher-meta-text">Показаны все слова словаря</div>
                        <button type="button" data-action="apply-dictionary-preset" class="btn btn-accent btn-sm">Заполнить из словаря</button>
                    </div>

                    <div id="multipleChoiceFields" class="is-hidden">
                        <label class="teacher-label">Вопрос:</label>
                        <textarea id="multipleChoiceQuestion" rows="2" class="teacher-textarea teacher-field"></textarea>
                        <label class="teacher-label">Варианты ответов:</label>
                        <div id="quizOptionsList"></div>
                        <div class="teacher-inline-input">
                            <input type="text" id="newQuizOptionInput" placeholder="Введите вариант ответа" class="teacher-input">
                            <button type="button" data-action="add-quiz-option" class="btn btn-primary btn-xs">+ Добавить вариант</button>
                        </div>
                        <label class="teacher-label teacher-label-spaced">Индекс правильного ответа:</label>
                        <input type="number" id="multipleChoiceCorrect" min="0" value="0" class="teacher-input">
                    </div>

                    <div id="fillBlanksFields" class="is-hidden">
                        <label class="teacher-label">Вопрос:</label>
                        <textarea id="fillBlanksQuestion" rows="2" class="teacher-textarea teacher-field"></textarea>
                        <label class="teacher-label">Предложение с `___`:</label>
                        <textarea id="fillBlanksSentence" rows="2" class="teacher-textarea teacher-field"></textarea>
                        <label class="teacher-label">Варианты через запятую:</label>
                        <input type="text" id="fillBlanksOptions" class="teacher-input teacher-field">
                        <label class="teacher-label">Правильный ответ:</label>
                        <input type="text" id="fillBlanksCorrect" class="teacher-input">
                    </div>

                    <div id="matchingFields" class="is-hidden">
                        <label class="teacher-label">Вопрос:</label>
                        <textarea id="matchingQuestion" rows="2" class="teacher-textarea teacher-field"></textarea>
                        <label class="teacher-label">Левая колонка, по одной строке:</label>
                        <textarea id="matchingLeft" rows="4" class="teacher-textarea teacher-field"></textarea>
                        <label class="teacher-label">Правая колонка, по одной строке:</label>
                        <textarea id="matchingRight" rows="4" class="teacher-textarea"></textarea>
                    </div>

                    <div id="wordOrderFields" class="is-hidden">
                        <label class="teacher-label">Вопрос:</label>
                        <textarea id="wordOrderQuestion" rows="2" class="teacher-textarea teacher-field"></textarea>
                        <label class="teacher-label">Правильная фраза:</label>
                        <input type="text" id="wordOrderAnswer" class="teacher-input teacher-field">
                        <label class="teacher-label">Слова через запятую:</label>
                        <input type="text" id="wordOrderOptions" class="teacher-input">
                    </div>

                    <div id="audioPickFields" class="is-hidden">
                        <label class="teacher-label">Вопрос:</label>
                        <textarea id="audioPickQuestion" rows="2" class="teacher-textarea teacher-field"></textarea>
                        <label class="teacher-label">Варианты через запятую:</label>
                        <input type="text" id="audioPickOptions" class="teacher-input teacher-field">
                        <label class="teacher-label">Индекс правильного ответа:</label>
                        <input type="number" id="audioPickCorrect" min="0" value="0" class="teacher-input">
                    </div>

                    <div id="flashcardsFields" class="is-hidden">
                        <label class="teacher-label">Заголовок/вопрос:</label>
                        <textarea id="flashcardsQuestion" rows="2" class="teacher-textarea teacher-field"></textarea>
                        <label class="teacher-label">Слово:</label>
                        <input type="text" id="flashcardsWord" class="teacher-input teacher-field">
                        <label class="teacher-label">Перевод:</label>
                        <input type="text" id="flashcardsTranslation" class="teacher-input teacher-field">
                        <label class="teacher-label">Подсказка:</label>
                        <input type="text" id="flashcardsHint" class="teacher-input">
                    </div>

                    <div class="teacher-media-block">
                        <h4 class="teacher-section-title">Медиа и предпросмотр</h4>
                        <label class="teacher-label">Изображение:</label>
                        <input type="file" id="stepImageFile" accept="image/*" class="teacher-file">
                        <input type="hidden" id="stepImageUrl">
                        <div id="stepImageMeta" class="teacher-meta-text">Файл не загружен</div>
                        <label class="teacher-label">Аудио:</label>
                        <input type="file" id="stepAudioFile" accept="audio/*" class="teacher-file">
                        <input type="hidden" id="stepAudioUrl">
                        <div id="stepAudioMeta" class="teacher-meta-text">Файл не загружен</div>
                        <label class="teacher-label">Видеофайл:</label>
                        <input type="file" id="stepVideoFile" accept="video/*" class="teacher-file">
                        <input type="hidden" id="stepVideoFileUrl">
                        <div id="stepVideoFileMeta" class="teacher-meta-text">Файл не загружен</div>
                        <label class="teacher-label">Ссылка на видео:</label>
                        <input type="url" id="stepVideoUrl" placeholder="https://youtube.com/... или другая ссылка" data-input-action="render-step-media-preview" class="teacher-input">
                        <div id="mediaUploadStatus" class="teacher-upload-status"></div>
                        <div id="stepMediaPreview" class="teacher-field"></div>
                    </div>

                    <div class="teacher-modal-actions teacher-modal-actions--end teacher-modal-actions--spaced">
                        <button type="button" data-action="close-step-modal" class="btn btn-muted">Отмена</button>
                        <button type="button" data-action="save-step" class="btn btn-success">Сохранить шаг</button>
                    </div>
                </div>
            </div>


