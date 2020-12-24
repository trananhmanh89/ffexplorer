<template>
    <div class="d-sidebar-items" :style="{ height }">
        <table class="d-item-list">
            <tr 
                class="d-item" 
                v-for="item in filtedList" 
                :key="item.name"
                :class="{active: item.name === activeTable}"
                @click="selectTable(item.name)">
                <td>{{item.name}}</td>
                <td class="item-size">
                    <span>{{parseSize(item.size)}}</span>
                    <div class="item-size-percent" :style="{width: getSizePercent(item.size) + '%' }"></div>
                </td>
            </tr>
        </table>
    </div>
</template>

<script>
import debounce from 'lodash/debounce';
import Vue from 'vue';

export default {

    data() {
        return {
            height: '0px',
            list: [],
            maxSize: 0,
        }
    },

    mounted() {
        setTimeout(() => {
            this.setListHeight();
        });

        jQuery(window).on('resize.ffexplorer', () => {
            this.setListHeight();
        });

        this.$ajax({
            task: 'db.tableList'
        })
        .then(res => {
            if (res.error) {
                return alert(res.error);
            }

            if (res.data) {
                Vue.set(this, 'list', res.data);
                
                const sizes = res.data.map(item => +item.size);

                this.maxSize = sizes.reduce((a, b) => {
                    return Math.max(a, b);
                });
            }
        });
    },

    computed: {
        filtedList() {
            const keyword = this.$store.state.db_keyword.toLowerCase();
        
            return this.list.filter(item => {
                return item.name.toLowerCase().indexOf(keyword) > -1;
            });
        },

        activeTable() {
            return this.$store.state.activeTable;
        }
    },

    methods: {
        selectTable(name) {
            this.$store.commit('setActiveTable', name);
        },

        setListHeight: debounce(function() {
            const $ = jQuery;
            const wHeight = $(window).height();

            if ($('.d-sidebar-items').length) {
                this.height = (wHeight - $('.d-sidebar-items').offset().top - 45) + 'px';
            }
        }, 100),

        parseSize(size) {
            const kb = Math.round(size / 1024);

            if (kb < 1024) {
                return kb + ' kB';
            }
            
            const mb = Math.round(size / 1024 / 1024);
            if (mb < 1024) {
                return mb + ' mB';
            }

            const gB = Math.round(size / 1024 / 1024);
            return gB + ' gB';
        },

        getSizePercent(size) {
            const rouned = Math.round(size * 100 * 100 / this.maxSize);
            return rouned / 100;
        }
    }
}
</script>

<style lang="scss">
.d-sidebar-items {
    overflow: auto;
    border: solid 1px #ddd;
    padding: 5px;

    .d-item-list {
        min-width: 100%;

        > tr {
            border-bottom: dashed 1px #ddd;
            cursor: pointer;
            user-select: none;
            transition: background-color 300ms;

            &:hover {
                background-color: rgba(221, 221, 221, 0.32);
            }

            &.active {
                background-color: #ccc;

                .item-size-percent {
                    background-color: #03A9F4;
                }
            }

            > td {
                padding: 3px;
            }
        }

        .item-size {
            position: relative;
            text-align: right;
            white-space: nowrap;
            border-left: dashed 1px #eaeaea;

            span {
                position: relative;
                z-index: 1;
            }

            .item-size-percent {
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
                right: 0;
                background-color: #d4d4d4;
                opacity: 0.5;
                border-radius: 3px;
                z-index: 0;
                transition: background-color 300ms;
            }
        }
    }
}
</style>