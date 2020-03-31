<template>
    <div class="d-content" 
        v-loading="loading">
        <div class="d-content-header">
            <el-pagination
                layout="prev, pager, next"
                :page-size="50"
                :hide-on-single-page="true"
                :current-page.sync="currentPage"
                :total="total"
                @current-change="changePage">
            </el-pagination>
        </div>
        <div class="d-content-inner" :style="{height, overflow: this.loading ? 'hidden' : 'auto'}">
            <table class="d-content-table" border="1" bordercolor="#ddd">
                <thead>
                    <tr>
                        <th v-for="column in columns" :key="column.name">{{column.name}}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, row) in items" 
                        :key="row" 
                        :class="{selected: activeRow === row}"
                        @click="selectRow(row)">
                        <td v-for="(value, column) in item" 
                            :key="column"
                            :class="{selected: activeNode === (row + column + '')}"
                            @click="selectNode(row + column + '', item, column)">{{value.substring(0, 100)}}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <el-dialog
            width="60%"
            :close-on-click-modal="false"
            :close-on-press-escape="!saving"
            :show-close="!saving"
            :destroy-on-close="true"
            :custom-class="'dialog-edit'"
            :title="activeColumn + ' - Editor'"
            :visible.sync="dialogEdit">
            <textarea 
                v-model="dialogValue" 
                style="width: 100%; box-sizing: border-box;" 
                rows="15"
                :disabled="saving"></textarea>
            <span slot="footer" class="dialog-footer">
                <el-button 
                    size="small" 
                    :disabled="saving"
                    @click="dialogEdit = false">Close</el-button>
                <el-button 
                    size="small" 
                    type="primary" 
                    :loading="saving"
                    @click="saveNode">Save</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>
import Vue from 'vue';
import debounce from 'lodash/debounce';

export default {
    data() {
        return {
            loading: false,
            columns: [],
            total: 0,
            items: [],
            height: '0px',
            currentPage: 1,
            activeNode: '',
            activeColumn: '',
            activeRow: -1,
            activeItem: {},
            dialogValue: '',
            dialogEdit: false,
            saving: false,
        }
    },

    mounted() {
        setTimeout(() => {
            this.setContentHeight();
        });

        jQuery(window).on('resize.ffexplorer', () => {
            this.setContentHeight();
        });
    },

    computed: {
        activeTable() {
            return this.$store.state.activeTable;
        },
    },

    watch: {
        activeTable(name) {
            this.resetActiveNode();
            this.currentPage = 1;
            this.initTable(name);
        },
    },

    methods: {
        saveNode() {
            this.saving = true;

            const cols = this.getConditionColumns();
            const condition = {};
            cols.forEach(col => {
                condition[col] = this.activeItem[col]
            });

            this.$ajax({
                task: 'db.saveNode',
                table: this.$store.state.activeTable,
                condition: JSON.stringify(condition),
                column: this.activeColumn,
                value: this.dialogValue,
            })
            .then(res => {
                if (res.error) {
                    return alert(res.error);
                }

                this.$message({
                    type: 'success',
                    message: 'Save successfully',
                });

                this.activeItem[this.activeColumn] = res.result;
            })
            .finally(() => {
                this.saving = false;
            });
        },

        getConditionColumns() {
            const priCols = this.columns.filter(col => {
                return col.key === 'PRI';
            })
            .map(col => {
                return col.name;
            });

            if (priCols.length) {
                return priCols;
            } else {
                return this.columns.map(col => col.name);
            }
        },

        resetActiveNode() {
            this.activeNode = '';
            this.activeColumn = '';
            this.activeRow = -1;
            Vue.set(this, 'activeItem', {});
        },

        changePage(page) {
            this.resetActiveNode();
            this.initTable(this.activeTable, page);
        },

        selectRow(row) {
            this.activeRow = row;
        },

        selectNode(nodeId, item, column) {
            if (this.activeNode !== nodeId) {
                this.activeNode = nodeId;
                this.activeColumn = column;
                this.dialogValue = item[column];
                Vue.set(this, 'activeItem', item);
            } else {
                this.dialogEdit = true;
            }
        },

        setContentHeight: debounce(function() {
            const $ = jQuery;
            const wHeight = $(window).height();

            this.height = (wHeight - $('.d-content').offset().top - 73) + 'px';
        }, 100),

        initTable(name, page) {
            page = page ? page : 1;

            this.loading = true;

            this.$ajax({
                task: 'db.initTable',
                name,
                page,
            })
            .then(res => {
                if (res.error) {
                    return alert(res.error);
                }

                if (res.data) {
                    this.total = +res.data.total;
                    Vue.set(this, 'columns', res.data.columns);
                    Vue.set(this, 'items', res.data.items);

                    const $inner = this.$el.querySelector('.d-content-inner');
                    $inner.scrollTop = 0;
                    $inner.scrollLeft = 0;
                }
            })
            .finally(() => {
                this.loading = false;
            });
        }
    },
}
</script>

<style lang="scss">
.d-content {
    flex: 1;
    margin-top: 5px;
    margin-left: 10px;
    overflow: hidden;

    .d-content-header {
        min-height: 38px;
    }

    .d-content-inner {
        overflow: auto;
        border: solid 1px #ddd;
    }

    .d-content-table {
        th {
            text-align: unset;
            max-width: 300px;
            padding: 3px 10px;
        }

        tr {
            transition: background-color 300ms;

            &.selected {
                background-color: rgba(0, 0, 0, 0.1);
            }

            > td {
                max-width: 300px;
                padding: 3px 10px;
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
                user-select: none;
                transition: background-color 300ms;

                &.selected {
                    background-color: rgba(103, 186, 224, 0.73);
                    outline: dashed 1px #3a8ee6;
                }
            }
        }
    }

    .dialog-edit {
        .el-dialog__body {
            padding: 10px;
        }
        
        .el-dialog__footer {
            padding: 10px;
        }
    }
}
</style>