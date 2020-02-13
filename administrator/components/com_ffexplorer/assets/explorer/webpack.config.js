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
            chunkFilename: '[name].app.js',
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
                    use: [
                        {
                            loader: 'file-loader',
                            options: {
                                name: '[name].[ext]',
                                outputPath: 'fonts',
                            },
                        },
                    ],
                },
                {
                    test: /\.m?js$/,
                    use: {
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
                },
            ]
        },

        plugins: [
            new VueLoaderPlugin(),
            new MiniCssExtractPlugin({
                filename: 'app.css',
                chunkFilename: '[name].css',
            }),
        ],
    }
}