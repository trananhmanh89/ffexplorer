<template>
    <div class="e-content-editor" :style="{height: editorHeight}"></div>
</template>

<script>
import {EventBus} from '../event-bus';
import debounce from 'lodash/debounce';

let editor = false;
let eData = {};

export default {
    data() {
        return {
            editorHeight: '0px',
            current: '',
            initedSnippet: false,
        }
    },

    mounted() {
        eData = {};
        editor = false;

        EventBus.$on('fileNameChanged', (newFile, oldFile) => {
            if (eData[oldFile.path]) {
                const data = eData[oldFile.path];

                data.path = newFile.path;
                eData[newFile.path] = data;

                delete eData[oldFile.path];

                if (this.current === oldFile.path) {
                    this.current = newFile.path;
                }
            }
        });

        EventBus.$on('fileDeleted', deletedFile => {
            const data = eData[deletedFile.path];

            if (!data) {
                return;
            }

            data.readOnly = true;

            if (this.current === deletedFile.path) {
                editor.updateOptions({readOnly: true});
            }
        });

        EventBus.$on('folderNameChanged', (newFolder, oldFolder) => {
            for (const key in eData) {
                const idx = key.indexOf(oldFolder.path);

                if (idx !== 0) {
                    return;
                }

                const tail = key.slice(oldFolder.path.length - key.length);
                const newKey = newFolder.path + tail;

                eData[newKey] = eData[key];
                delete eData[key];

                if (this.current === key) {
                    this.current = newKey;
                }
            }
        });

        EventBus.$on('folderDeleted', deletedFolder => {
            for (const key in eData) {
                const idx = key.indexOf(deletedFolder.path);

                if (idx !== 0) {
                    return;
                }

                eData[key].readOnly = true;

                if (this.current === key) {
                    editor.updateOptions({readOnly: true});
                }
            }
        });

        jQuery(window).on('resize.ffexplorer', () => {
            this.computeEditorHeight();
            this.resizeEditorLayout();
        });

        setTimeout(() => {
            const loading = this.$loading({
                lock: true,
                text: 'Refreshing',
                spinner: 'el-icon-loading',
                background: 'rgba(0, 0, 0, 0.7)',
                customClass: 'compress-loading'
            });

            const baseUrl = Joomla.getOptions('system.paths').baseFull;

            this.loader().then(() => {
                window.require.config({
                    paths: {
                        'vs': baseUrl + 'components/com_ffexplorer/assets/monaco/min/vs'
                    }
                });

                this.createEditor().then(() => {
                    loading.close();
                    this.resizeEditorLayout();
                });
            })
        });

        setTimeout(() => {
            this.computeEditorHeight();
        });
    },

    methods: {
        loader() {
            return new Promise((resolve, reject) => {
                const head = document.getElementsByTagName('head')[0]
                const script = document.createElement('script')

                script.type = 'text/javascript'
                script.addEventListener('load', function () {
                    resolve(script)
                })
                script.src = Joomla.getOptions('system.paths').root + '/administrator/components/com_ffexplorer/assets/monaco/min/vs/loader.js';

                head.appendChild(script)
            });
        },

        async initEditor(file, force) {
            if (!editor) {
                await this.createEditor();
            }

            const path = file.path;

            if (this.current === path && !force) {
                return;
            }

            if (this.current && eData[this.current]) {
                eData[this.current].model = editor.getModel();
                eData[this.current].state = editor.saveViewState();
            }

            this.current = path;

            if (!eData[path] || force) {
                const language = this.parseLanguage(file.name);
                const sampleModel = monaco.editor.createModel('loading...', language);

                const _data = {
                    model: sampleModel,
                    state: {},
                    lastSaved: sampleModel.getAlternativeVersionId(),
                    status: 'opening',
                    readOnly: true,
                    path: path,
                };

                eData[path] = _data;

                this.emitEditorStatus(_data);
                this.$store.commit('lock', path);

                this.getFileContent(path).then(res => {
                    _data.status = 'normal';
                    _data.readOnly = false;
                    _data.model = monaco.editor.createModel(res.content, language);
                    _data.model.onDidChangeContent(() => {
                        if (_data.status === 'saving') {
                            return;
                        }

                        this.checkDirty(_data);
                        this.emitEditorStatus(_data);
                    });

                    this.emitEditorStatus(_data);

                    if (this.current === _data.path) {
                        editor.layout();
                        this.updateEditor(_data);
                    }
                })
                .catch(error => {
                    alert('Could not open this file');

                    _data.status = 'normal';
                    this.emitEditorStatus(_data);
                    this.$emit('removeFile', _data.path);
                })
                .finally(() => {
                    this.$store.commit('unlock', path);
                });
            }
            
            this.updateEditor(eData[path]);
        },

        checkDirty(data) {
            const isDirty = data.lastSaved !== data.model.getAlternativeVersionId();
            data.status = isDirty ? 'dirty' : 'normal';
        },

        updateEditor({model, state, readOnly}) {
            editor.setModel(model);
            editor.restoreViewState(state);
            editor.updateOptions({
                readOnly: readOnly
            });

            editor.layout();
            editor.focus();
        },

        parseLanguage(name) {
            const langs = {
/*************  ✨ Windsurf Command 🌟  *************/
                js: 'javascript',
                php: 'php',
                scss: 'scss',
                css: 'css',
                gitignore: 'markdown',
                less: 'less',
                sql: 'mysql',
                ini: 'ini',
                xml: 'xml',
                html: 'html',
                ini: 'ini',
                js: 'javascript',
                svg: 'html',
                json: 'json',
                less: 'less',
                md: 'markdown',
                php: 'php',
                scss: 'scss',
                sql: 'mysql',
                gitignore: 'markdown',
/*******  3e7cddcf-6fa6-4230-8e5c-af2974c6979e  *******/
            };

            if (name.indexOf('.') === -1) {
                return '';
            }

            const ext = name.split('.').pop();
            return langs[ext] ? langs[ext] : '';
        },

        saveContent(path, content) {
            return new Promise((resolve, reject) => {
                this.$ajax({
                    task: 'explorer.saveContent',
                    path,
                    content,
                })
                .then(res => {
                    resolve(res);
                })
                .catch(error => {
                    reject(error);
                });
            });
        },

        emitEditorStatus({status, path}) {
            this.$emit('statusChange', {
                status,
                path,
            });
        },

        getFileContent(path) {
            return new Promise((resolve, reject) => {
                this.$ajax({
                    task: 'explorer.openFile',
                    path: path
                })
                .then(res => {
                    if (!res || res.error) {
                        reject();
                    } else {
                        resolve(res);
                    }
                })
                .catch(error => {
                    reject();
                });
            });
        },

        addSnippet(monaco) {
            monaco.languages.registerCompletionItemProvider('php', {
                provideCompletionItems: function(model, position) {
                    const textUntilPosition = model.getValueInRange({startLineNumber: 1, startColumn: 1, endLineNumber: position.lineNumber, endColumn: position.column});
                    const fragsA = textUntilPosition.split('<?php');
                    const fragsB = textUntilPosition.split('?>');
                    if (textUntilPosition.indexOf('<?php') === -1) {
                        return;
                    }

                    if (fragsB.length === 1 || fragsB.pop().length > fragsA.pop().length) {
                        const word = model.getWordUntilPosition(position);
                        const range = {
                            startLineNumber: position.lineNumber,
                            endLineNumber: position.lineNumber,
                            startColumn: word.startColumn,
                            endColumn: word.endColumn
                        };
                        return {
                            suggestions: [
                                {
                                    label: 'pre',
                                    kind: monaco.languages.CompletionItemKind.Function,
                                    documentation: "Print Result",
                                    insertText: "echo '<pre>' . print_r(${1:true}, 1) . '</pre>';",
                                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                                    range: range
                                },
                                {
                                    label: 'd',
                                    kind: monaco.languages.CompletionItemKind.Function,
                                    documentation: "Print Result and Die",
                                    insertText: "die('<pre>'.print_r(${1:true}, 1).'</pre>');",
                                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                                    range: range
                                }
                            ]
                        };
                    }
                }
            });

            monaco.languages.registerCompletionItemProvider('javascript', {
                provideCompletionItems: function(model, position) {
                    const word = model.getWordUntilPosition(position);
                    const range = {
                        startLineNumber: position.lineNumber,
                        endLineNumber: position.lineNumber,
                        startColumn: word.startColumn,
                        endColumn: word.endColumn
                    };
                    return {
                        suggestions: [
                            {
                                label: 'cl',
                                kind: monaco.languages.CompletionItemKind.Function,
                                documentation: "console.log",
                                insertText: "console.log(${1:true});",
                                insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet,
                                range: range
                            },
                        ]
                    };
                }
            });
        },

        createEditor() {
            return new Promise((resolve, reject) => {
                window.require(['vs/editor/editor.main'], () => {
                    const monaco = window.monaco;
                    if (!this.initedSnippet) {
                        this.addSnippet(monaco);
                        this.initedSnippet = true;
                    }

                    if (!editor) {
                        editor = monaco.editor.create(this.$el, {
                            model: null,
                        });
                    }

                    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, () => {
                        const path = this.current;
                        const data = eData[path];

                        if (!data || data.status === 'saving' || data.readOnly) {
                            return;
                        }

                        data.status = 'saving';

                        this.emitEditorStatus(data);
                        this.$store.commit('lock', path);

                        const content = editor.getValue();
                        const tmpSaved = data.model.getAlternativeVersionId();

                        this.saveContent(path, content).then(res => {
                            if (res.error) {
                                alert(res.error)
                            } else {
                                data.lastSaved = tmpSaved;
                            }

                            this.$store.commit('unlock', path);
                        })
                        .catch(error => {
                            alert('save error');
                            console.log(error);

                            this.$store.commit('unlock', path);
                        })
                        .finally(() => {
                            this.checkDirty(data);
                            this.emitEditorStatus(data);
                        });
                    });

                    editor.addAction({
                        id: 'copy-relative-path',
                        label: 'Copy Relative Path',
                        contextMenuGroupId: 'navigation',
                        run: ed => {
                            navigator.clipboard.writeText(this.current).then(() => {
                                this.$message({
                                    type: 'success',
                                    message: 'Copied'
                                });
                            })
                        }
                    })

                    resolve();
                });
            });
        },

        removeFile(path) {
            delete eData[path];
        },

        resetEditor() {
            editor && editor.dispose();
            editor = '';
            this.current = '';
        },

        resizeEditorLayout: debounce(function() {
            editor && editor.layout();
        }, 100),

        computeEditorHeight: debounce(function() {
            const $ = jQuery;
            const wHeight = $(window).height();

            if ($('.e-folders').length) {
                this.editorHeight = (wHeight - $('.e-folders').offset().top - 35) + 'px';
            }
        }, 100),
    }
}
</script>

<style lang="scss">
.e-content-editor {
    border: solid 1px #ddd;
}
</style>