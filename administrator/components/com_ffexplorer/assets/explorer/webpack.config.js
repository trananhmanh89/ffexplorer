const path = require('path');
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = env => {
    const isDev = env && env.dev;

    return {
        watch: isDev ? true : false,

        mode: isDev ? 'development' : 'production',

        entry: './src/index.js',

        output: {
            filename: 'app.js',
            path: path.resolve(__dirname, 'dist'),
        },

        module: {
            rules: [
                {
                    test: /\.vue$/,
                    loader: 'vue-loader',
                },
                {
                    test: /\.(css|scss)$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        'css-loader',
                        'sass-loader',
                    ]
                },
                {
                    test: /\.(ttf|woff)$/i,
                    type: 'asset/resource',
                    generator: {
                        filename: 'fonts/[name][ext]'
                    }
                },
                {
                    test: /\.m?js$/,
                    loader: 'babel-loader',
                    options: {
                        plugins: [
                            [
                                'component',
                                {
                                    libraryName: 'element-ui',
                                    styleLibraryName: 'theme-chalk',
                                },
                            ],
                        ],
                    },
                },
            ]
        },



        plugins: [
            new VueLoaderPlugin(),
            new MiniCssExtractPlugin({
                filename: 'app.css',
            }),
        ],
    }
}