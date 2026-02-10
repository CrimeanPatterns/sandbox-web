'use strict'

class PluginLauncher {

    constructor() {
        this._plugins = [];
    }

    use(plugin) {
        if (typeof plugin !== 'object' || !plugin._isPuppeteerExtraPlugin) {
            console.error(`Warning: Plugin is not derived from PuppeteerExtraPlugin, ignoring.`, plugin);
            return this;
        }
        if (!plugin.name) {
            console.error(`Warning: Plugin with no name registering, ignoring.`, plugin);
            return this;
        }
        if (plugin.requirements.has('dataFromPlugins')) {
            plugin.getDataFromPlugins = this.getPluginData.bind(this);
        }
        plugin._register(Object.getPrototypeOf(plugin));
        this._plugins.push(plugin);
        debug('plugin registered', plugin.name);
        return this;
    }

    async launch(options) {
        // Ensure there are certain properties (e.g. the `options.args` array)
        var options = {};
        this.resolvePluginDependencies();
        this.orderPlugins();
        // Give plugins the chance to modify the options before launch
        options = await this.callPluginsWithValue('beforeLaunch', options);
        const opts = {
            context: 'launch',
            options,
            defaultArgs: this.defaultArgs
        };

        // https://github.com/puppeteer/puppeteer/blob/master/docs/api.md#pageevaluateonnewdocumentpagefunction-args
        const page = {
            evaluateOnNewDocument(fn, ...args) {
                debug('evaluateOnNewDocument');
                fn(...args);
            },
            browser () {
                return {
                    userAgent() {
                        return "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36";
                    }
                }
            },
            _client: {
                send (...args) {
                    debug('_client.send', ...args);
                }
            }
        };

        // Let's check requirements after plugin had the chance to modify the options
        const browser = {
            setMaxListeners(n) {
            },
            on(event, listener) {
                debug('on', event, listener);
                if (event === 'targetcreated') {
                    debug('calling targetcreated');
                    listener({
                        "type": function() {
                            return "page";
                        },
                        "page": function() {
                            return page;
                        }
                    });
                }
            }
        };
        this.checkPluginRequirements(opts);
        return await this.callPlugins('_bindBrowserEvents', browser, opts);
    }

    resolvePluginDependencies() {
        // Request missing dependencies from all plugins and flatten to a single Set
        const missingPlugins = this._plugins
                .map(p => p._getMissingDependencies(this._plugins))
                .reduce((combined, list) => {
                    return new Set([...combined, ...list]);
                }, new Set());
        if (!missingPlugins.size) {
            debug('no dependencies are missing');
            return;
        }
        debug('dependencies missing', missingPlugins);
        // Loop through all dependencies declared missing by plugins
        for (let name of [...missingPlugins]) {
            // Check if the dependency hasn't been registered as plugin already.
            // This might happen when multiple plugins have nested dependencies.
            if (this.pluginNames.includes(name)) {
                debug(`ignoring dependency '${name}', which has been required already.`);
                continue;
            }
            // We follow a plugin naming convention, but let's rather enforce it <3
            name = name.startsWith('puppeteer-extra-plugin')
                    ? name
                    : `puppeteer-extra-plugin-${name}`;
            // In case a module sub resource is requested print out the main package name
            // e.g. puppeteer-extra-plugin-stealth/evasions/console.debug => puppeteer-extra-plugin-stealth
            const packageName = name.split('/')[0];
            let dep = null;
            try {
                // Try to require and instantiate the stated dependency
                dep = require(name)();
                // Register it with `puppeteer-extra` as plugin
                this.use(dep);
            } catch (err) {
                console.warn(`
          A plugin listed '${name}' as dependency,
          which is currently missing. Please install it:
    
          yarn add ${packageName}
    
          Note: You don't need to require the plugin yourself,
          unless you want to modify it's default settings.
          `);
                throw err;
            }
            // Handle nested dependencies :D
            if (dep.dependencies.size) {
                this.resolvePluginDependencies();
            }
        }
    }

    /**
     * Order plugins that have expressed a special placement requirement.
     *
     * This is useful/necessary for e.g. plugins that depend on the data from other plugins.
     *
     * @todo Support more than 'runLast'.
     * @todo If there are multiple plugins defining 'runLast', sort them depending on who depends on whom. :D
     *
     * @private
     */
    orderPlugins() {
        debug('orderPlugins:before', this.pluginNames);
        const runLast = this._plugins
                .filter(p => p.requirements.has('runLast'))
                .map(p => p.name);
        for (const name of runLast) {
            const index = this._plugins.findIndex(p => p.name === name);
            this._plugins.push(this._plugins.splice(index, 1)[0]);
        }
        debug('orderPlugins:after', this.pluginNames);
    }

    /**
     * Lightweight plugin requirement checking.
     *
     * The main intent is to notify the user when a plugin won't work as expected.
     *
     * @todo This could be improved, e.g. be evaluated by the plugin base class.
     *
     * @private
     */
    checkPluginRequirements(opts = {}) {
        for (const plugin of this._plugins) {
            for (const requirement of plugin.requirements) {
                if (opts.context === 'launch' &&
                        requirement === 'headful' &&
                        opts.options.headless) {
                    console.warn(`Warning: Plugin '${plugin.name}' is not supported in headless mode.`);
                }
                if (opts.context === 'connect' && requirement === 'launch') {
                    console.warn(`Warning: Plugin '${plugin.name}' doesn't support puppeteer.connect().`);
                }
            }
        }
    }

    /**
     * Call plugins sequentially with the same values.
     * Plugins that expose the supplied property will be called.
     *
     * @param prop - The plugin property to call
     * @param values - Any number of values
     * @private
     */
    async callPlugins(prop, ...values) {
        for (const plugin of this.getPluginsByProp(prop)) {
            await plugin[prop].apply(plugin, values);
        }
    }

    /**
     * Get the names of all registered plugins.
     *
     * @member {Array<string>}
     * @private
     */
    get pluginNames() {
        return this._plugins.map(p => p.name);
    }

    /**
     * Call plugins sequentially and pass on a value (waterfall style).
     * Plugins that expose the supplied property will be called.
     *
     * The plugins can either modify the value or return an updated one.
     * Will return the latest, updated value which ran through all plugins.
     *
     * @param prop - The plugin property to call
     * @param value - Any value
     * @return The new updated value
     * @private
     */
    async callPluginsWithValue(prop, value) {
        for (const plugin of this.getPluginsByProp(prop)) {
            const newValue = await plugin[prop](value);
            if (newValue) {
                value = newValue;
            }
        }
        return value;
    }

    /**
     * Get all plugins that feature a given property/class method.
     *
     * @private
     */
    getPluginsByProp(prop) {
        return this._plugins.filter(plugin => prop in plugin);
    }

}

module.exports = new PluginLauncher()