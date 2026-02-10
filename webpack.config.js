const webpack = require('webpack');
const path = require('path');

const config = {
    entry: './js/debug-extension.ts',
    experiments: {
        outputModule: true
    },
    output: {
        path: path.resolve('./src/js/compiled'),
        filename: 'debug-extension.js',
        libraryTarget: "module"
    },
    module: {
        rules: [
            {
                test: /\.ts(x)?$/,
                loader: 'ts-loader'
            }
        ]
    },
    resolve: {
        extensions: [
            '.tsx',
            '.ts',
            '.js'
        ]
    },
    optimization: {
        minimize: false
    },
    devtool: 'source-map'
};

module.exports = config;