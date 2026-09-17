'use strict';

/**
 * Swagger UI plugin: log in from Swagger UI and authorize with the returned token.
 *
 * Two opt-in UI elements, both driven by the same configuration:
 *   - a "Login" button next to the top "Authorize" button (hidden while logged in), opening a dialog that lists the
 *     login operations, renders a form from the request body schema, sends the request and applies the returned token;
 *   - an "Authorize" / "Logout" button in the response body of a login operation executed with "Try it out",
 *     next to the "Download" button.
 *
 * Both apply the token to the configured security scheme (padlocks switch to "authorized", subsequent requests
 * send the Authorization header, `persistAuthorization` is honoured), so no more copy/pasting the token into the
 * "Authorize" dialog. Login operations are public by nature: unless they declare `security` explicitly, the plugin
 * sets `security: []` on them, so Swagger UI shows no padlock and never sends the Authorization header to them.
 *
 * A login operation is detected by either:
 *   - the `x-apiplatform-login` vendor extension on the OpenAPI operation, or
 *   - the `apiPlatformLogin` key of the Swagger UI configuration (`swagger_ui_extra_configuration`),
 *     matched by `operationId` or by `path` (+ optional `method`).
 *
 * Configuration (`apiPlatformLogin`), either a list of mappings or an object:
 *   {
 *     operations: [ ...mappings ],  // login operations that are not marked with the vendor extension
 *     dialog: true,                 // "Login" button + dialog next to "Authorize" (default true)
 *     responseButton: true,         // "Authorize" / "Logout" button in the response body (default true)
 *   }
 *
 * Mapping shape (a single object or a list of objects):
 *   {
 *     securityScheme: 'JWT',  // required, key of components.securitySchemes
 *     tokenPath: 'token',     // dot path in the JSON response body, defaults to "token"
 *     prefix: 'Bearer ',      // apiKey schemes only, defaults to "Bearer " for the Authorization header
 *     label: 'Authorize',     // button label, defaults to "Authorize"
 *     logoutLabel: 'Logout',  // button label once authorized, defaults to "Logout"
 *     operationId: '...',     // global configuration only
 *     path: '/auth',          // global configuration only
 *     method: 'post',         // global configuration only
 *   }
 */
(function () {
    const EXTENSION_KEY = 'x-apiplatform-login';
    const CONFIG_KEY = 'apiPlatformLogin';
    const HTTP_METHODS = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace', 'query'];
    const PRIMITIVE_TYPES = ['string', 'number', 'integer', 'boolean'];

    function toJs(value) {
        return value && typeof value.toJS === 'function' ? value.toJS() : value;
    }

    function getByPath(object, path) {
        return String(path).split('.').reduce(function (accumulator, key) {
            return accumulator !== null && typeof accumulator === 'object' ? accumulator[key] : undefined;
        }, object);
    }

    function normalizeMappings(raw) {
        raw = toJs(raw);
        if (!raw) {
            return [];
        }

        return (Array.isArray(raw) ? raw : [raw])
            .filter(function (mapping) {
                return mapping && typeof mapping === 'object' && typeof mapping.securityScheme === 'string' && mapping.securityScheme !== '';
            })
            .map(function (mapping) {
                return {
                    securityScheme: mapping.securityScheme,
                    tokenPath: typeof mapping.tokenPath === 'string' && mapping.tokenPath !== '' ? mapping.tokenPath : 'token',
                    prefix: typeof mapping.prefix === 'string' ? mapping.prefix : null,
                    label: typeof mapping.label === 'string' && mapping.label !== '' ? mapping.label : 'Authorize',
                    logoutLabel: typeof mapping.logoutLabel === 'string' && mapping.logoutLabel !== '' ? mapping.logoutLabel : 'Logout',
                    operationId: typeof mapping.operationId === 'string' ? mapping.operationId : null,
                    path: typeof mapping.path === 'string' ? mapping.path : null,
                    method: typeof mapping.method === 'string' ? mapping.method.toLowerCase() : null,
                };
            });
    }

    /**
     * Normalizes the `apiPlatformLogin` configuration (a list of mappings, or an object with options).
     */
    function normalizeConfig(configs) {
        const raw = toJs((configs || {})[CONFIG_KEY]);
        const isOptions = raw && typeof raw === 'object' && !Array.isArray(raw) && undefined === raw.securityScheme;

        return {
            operations: normalizeMappings(isOptions ? raw.operations : raw),
            dialog: !isOptions || false !== raw.dialog,
            responseButton: !isOptions || false !== raw.responseButton,
        };
    }

    /**
     * Returns the login mappings of an operation (plain object or Immutable Map), or an empty list.
     */
    function matchMappings(operation, path, method, config) {
        const read = function (key) {
            if (!operation) {
                return undefined;
            }

            return typeof operation.get === 'function' ? operation.get(key) : operation[key];
        };

        // 1. Vendor extension on the operation
        const extension = read(EXTENSION_KEY);
        if (extension) {
            return normalizeMappings(extension);
        }

        // 2. Global configuration (swagger_ui_extra_configuration.apiPlatformLogin)
        const operationId = read('operationId');
        const lowerCaseMethod = String(method).toLowerCase();

        return config.operations.filter(function (mapping) {
            if (mapping.operationId) {
                return mapping.operationId === operationId;
            }

            return mapping.path === path && (!mapping.method || mapping.method === lowerCaseMethod);
        });
    }

    function resolveMappings(system, path, method) {
        const specSelectors = system.specSelectors;
        const operation = specSelectors.specResolvedSubtree(['paths', path, method])
            || specSelectors.specJson().getIn(['paths', path, method]);

        return matchMappings(operation, path, method, normalizeConfig(system.getConfigs()));
    }

    /**
     * Iterates over the operations of a plain JSON spec.
     */
    function forEachOperation(spec, callback) {
        if (!spec || typeof spec !== 'object' || !spec.paths || typeof spec.paths !== 'object') {
            return;
        }

        Object.keys(spec.paths).forEach(function (path) {
            const pathItem = spec.paths[path];
            if (!pathItem || typeof pathItem !== 'object') {
                return;
            }

            HTTP_METHODS.forEach(function (method) {
                const operation = pathItem[method];
                if (operation && typeof operation === 'object') {
                    callback(operation, path, method);
                }
            });
        });
    }

    /**
     * Login operations are public: without an explicit `security`, opt them out of the global security requirements
     * so that Swagger UI neither shows a padlock nor sends the (possibly expired) token to them.
     */
    function disableSecurityOnLoginOperations(spec, config) {
        forEachOperation(spec, function (operation, path, method) {
            if (undefined === operation.security && matchMappings(operation, path, method, config).length > 0) {
                operation.security = [];
            }
        });

        return spec;
    }

    /**
     * Lists the login operations of the loaded spec (for the dialog).
     */
    function collectLoginOperations(system) {
        const spec = system.specSelectors.specJson().toJS();
        const config = normalizeConfig(system.getConfigs());
        const operations = [];

        forEachOperation(spec, function (operation, path, method) {
            const mappings = matchMappings(operation, path, method, config);
            if (0 === mappings.length) {
                return;
            }

            operations.push({
                path: path,
                method: method,
                operationId: operation.operationId || null,
                label: operation.summary || operation.operationId || (method.toUpperCase() + ' ' + path),
                mappings: mappings,
                form: buildForm(operation, spec),
            });
        });

        return operations;
    }

    function resolveRef(schema, spec, depth) {
        depth = depth || 0;
        if (!schema || typeof schema !== 'object' || typeof schema.$ref !== 'string' || depth > 5) {
            return schema;
        }

        const prefix = '#/components/schemas/';
        if (!schema.$ref.startsWith(prefix)) {
            return schema;
        }

        const target = spec.components && spec.components.schemas ? spec.components.schemas[schema.$ref.slice(prefix.length)] : null;

        return target ? resolveRef(target, spec, depth + 1) : schema;
    }

    function primitiveType(schema) {
        let type = schema.type;
        if (Array.isArray(type)) {
            type = type.filter(function (candidate) {
                return 'null' !== candidate;
            })[0];
        }

        if (undefined === type && Array.isArray(schema.enum)) {
            return 'string';
        }

        return PRIMITIVE_TYPES.indexOf(type) >= 0 ? type : null;
    }

    /**
     * Builds the form definition of a login operation from its JSON request body schema.
     * Falls back to a raw JSON editor when the schema is not a flat object of primitive properties.
     */
    function buildForm(operation, spec) {
        const content = operation.requestBody && operation.requestBody.content ? operation.requestBody.content : {};
        const contentType = Object.keys(content).filter(function (type) {
            return /json/i.test(type);
        })[0] || Object.keys(content)[0] || 'application/json';

        const schema = resolveRef(content[contentType] ? content[contentType].schema : null, spec) || {};
        const properties = schema.properties && typeof schema.properties === 'object' ? schema.properties : null;
        const required = Array.isArray(schema.required) ? schema.required : [];
        const example = (content[contentType] && content[contentType].example) || schema.example || null;

        const fields = [];
        let flat = null !== properties;
        if (flat) {
            Object.keys(properties).forEach(function (name) {
                const property = resolveRef(properties[name], spec) || {};
                if (true === property.readOnly) {
                    return;
                }

                const type = primitiveType(property);
                if (null === type) {
                    flat = false;

                    return;
                }

                fields.push({
                    name: name,
                    type: type,
                    required: required.indexOf(name) >= 0,
                    enum: Array.isArray(property.enum) ? property.enum : null,
                    isPassword: 'password' === property.format || /pass|secret|token/i.test(name),
                    defaultValue: undefined !== property.default ? property.default : (example && typeof example === 'object' ? example[name] : undefined),
                });
            });
        }

        if (flat && fields.length > 0) {
            return {contentType: contentType, fields: fields, json: null};
        }

        const skeleton = {};
        if (properties) {
            Object.keys(properties).forEach(function (name) {
                if (true !== (properties[name] || {}).readOnly) {
                    skeleton[name] = '';
                }
            });
        }

        return {contentType: contentType, fields: null, json: JSON.stringify(example && typeof example === 'object' ? example : skeleton, null, 2)};
    }

    function parseBody(response) {
        const text = response.get('text');
        if (typeof text === 'string' && text.trim() !== '') {
            try {
                return JSON.parse(text);
            } catch (error) {
                // Not a JSON body
            }
        }

        const body = toJs(response.get('body') || response.get('obj'));

        return body && typeof body === 'object' ? body : null;
    }

    function getScheme(system, mapping) {
        return system.specSelectors.specJson().getIn(['components', 'securitySchemes', mapping.securityScheme]) || null;
    }

    function buildValue(scheme, mapping, token) {
        const type = scheme.get('type');

        if ('http' === type) {
            // Swagger UI adds the "Bearer " prefix itself for the bearer scheme
            return token;
        }

        if ('apiKey' === type) {
            let prefix = mapping.prefix;
            if (null === prefix) {
                const isAuthorizationHeader = 'header' === scheme.get('in')
                    && 'authorization' === String(scheme.get('name')).toLowerCase();
                prefix = isAuthorizationHeader ? 'Bearer ' : '';
            }

            return prefix !== '' && token.startsWith(prefix) ? token : prefix + token;
        }

        // oauth2 and openIdConnect are not supported
        return null;
    }

    function isAuthorizedWith(system, mapping, value) {
        const authorized = system.authSelectors.authorized();
        const current = authorized && authorized.getIn([mapping.securityScheme, 'value']);

        return current === value;
    }

    function authorize(system, mapping, token) {
        const scheme = getScheme(system, mapping);
        if (!scheme) {
            console.warn('[API Platform] Security scheme "' + mapping.securityScheme + '" is not defined in components.securitySchemes.');

            return false;
        }

        const value = buildValue(scheme, mapping, token);
        if (null === value) {
            console.warn('[API Platform] Security scheme "' + mapping.securityScheme + '" of type "' + scheme.get('type') + '" is not supported by the login plugin.');

            return false;
        }

        const payload = {};
        payload[mapping.securityScheme] = {name: mapping.securityScheme, schema: scheme.toJS(), value: value};

        // authorizeWithPersistOption honours the persistAuthorization configuration
        system.authActions.authorizeWithPersistOption(payload);

        return true;
    }

    function logout(system, mapping) {
        system.authActions.logoutWithPersistOption([mapping.securityScheme]);
    }

    /**
     * Applies the tokens of a login response body to the mappings of the operation.
     * Returns the names of the authorized security schemes and the token paths that were not found.
     */
    function authorizeFromBody(system, mappings, body) {
        const authorized = [];
        const missing = [];

        mappings.forEach(function (mapping) {
            const token = body ? getByPath(body, mapping.tokenPath) : undefined;
            if (typeof token !== 'string' || '' === token) {
                missing.push(mapping.tokenPath);

                return;
            }

            if (authorize(system, mapping, token)) {
                authorized.push(mapping.securityScheme);
            }
        });

        return {authorized: authorized, missing: missing};
    }

    /**
     * Sends the login request through swagger-client (same as "Execute", including requestInterceptor and servers).
     */
    function submitLogin(system, operation, body) {
        const configs = system.getConfigs() || {};
        const request = {
            spec: system.specSelectors.specJson().toJS(),
            pathName: operation.path,
            method: operation.method,
            requestBody: body,
            requestContentType: operation.form.contentType,
            responseContentType: 'application/json',
            fetch: system.fn.fetch,
            requestInterceptor: configs.requestInterceptor,
            responseInterceptor: configs.responseInterceptor,
            contextUrl: window.location.href,
        };
        if (operation.operationId) {
            request.operationId = operation.operationId;
        }
        if (system.oas3Selectors && typeof system.oas3Selectors.selectedServer === 'function') {
            request.server = system.oas3Selectors.selectedServer() || undefined;
        }

        const normalize = function (response) {
            const text = typeof response.text === 'string' ? response.text : (response.data || '');
            let parsed = response.body && typeof response.body === 'object' ? response.body : null;
            if (!parsed && text) {
                try {
                    parsed = JSON.parse(text);
                } catch (error) {
                    // Not a JSON body
                }
            }

            return {status: Number(response.status) || 0, body: parsed, text: text};
        };

        return system.fn.execute(request).then(normalize, function (error) {
            if (error && error.response) {
                return normalize(error.response);
            }

            return {status: 0, body: null, text: (error && error.message) || String(error)};
        });
    }

    window.ApiPlatformSwaggerUiLoginPlugin = function (system) {
        const React = system.React;

        // Passes the login mappings from the live response down to the response body component
        const LoginContext = React.createContext(null);

        function LoginButton(props) {
            const mapping = props.mapping;
            const token = props.token;

            const scheme = getScheme(system, mapping);
            const value = scheme ? buildValue(scheme, mapping, token) : null;

            const state = React.useState(null !== value && isAuthorizedWith(system, mapping, value) ? 'authorized' : 'idle');
            const status = state[0];
            const setStatus = state[1];

            let label = mapping.label;
            if ('authorized' === status) {
                label = mapping.logoutLabel;
            } else if ('error' === status) {
                label = 'Failed (see console)';
            }

            return React.createElement('button', {
                type: 'button',
                className: 'api-platform-login-btn' + ('authorized' === status ? ' authorized' : ''),
                title: 'authorized' === status
                    ? 'Log out from the "' + mapping.securityScheme + '" security scheme'
                    : 'Apply the token from this response to the "' + mapping.securityScheme + '" security scheme',
                onClick: function () {
                    if ('authorized' === status) {
                        logout(system, mapping);
                        setStatus('idle');

                        return;
                    }

                    setStatus(authorize(system, mapping, token) ? 'authorized' : 'error');
                },
            }, label);
        }

        function initialValues(form) {
            const values = {};
            (form.fields || []).forEach(function (field) {
                if (undefined !== field.defaultValue && null !== field.defaultValue) {
                    values[field.name] = field.defaultValue;
                } else {
                    values[field.name] = 'boolean' === field.type ? false : '';
                }
            });

            return values;
        }

        function buildBody(form, values, jsonText) {
            if (!form.fields) {
                return JSON.parse(jsonText);
            }

            const body = {};
            form.fields.forEach(function (field) {
                const value = values[field.name];
                if ('boolean' === field.type) {
                    body[field.name] = Boolean(value);

                    return;
                }

                if ('' === value || undefined === value || null === value) {
                    return;
                }

                body[field.name] = 'number' === field.type || 'integer' === field.type ? Number(value) : value;
            });

            return body;
        }

        function LoginDialog(props) {
            const operations = props.operations;
            const CloseIcon = props.getComponent('CloseIcon', true);

            const indexState = React.useState(0);
            const index = indexState[0];
            const operation = operations[index];

            const valuesState = React.useState(initialValues(operation.form));
            const values = valuesState[0];
            const setValues = valuesState[1];
            const jsonState = React.useState(operation.form.json || '');
            const jsonText = jsonState[0];
            const setJsonText = jsonState[1];
            const submittingState = React.useState(false);
            const submitting = submittingState[0];
            const setSubmitting = submittingState[1];
            const resultState = React.useState(null);
            const result = resultState[0];
            const setResult = resultState[1];

            React.useEffect(function () {
                const onKeyDown = function (event) {
                    if ('Escape' === event.key) {
                        props.onClose();
                    }
                };
                document.addEventListener('keydown', onKeyDown);

                return function () {
                    document.removeEventListener('keydown', onKeyDown);
                };
            }, []);

            const selectOperation = function (newIndex) {
                indexState[1](newIndex);
                setValues(initialValues(operations[newIndex].form));
                setJsonText(operations[newIndex].form.json || '');
                setResult(null);
            };

            const submit = function (event) {
                event.preventDefault();

                let body;
                try {
                    body = buildBody(operation.form, values, jsonText);
                } catch (error) {
                    setResult({ok: false, status: 0, text: 'Invalid JSON: ' + error.message, authorized: []});

                    return;
                }

                setSubmitting(true);
                submitLogin(system, operation, body).then(function (response) {
                    setSubmitting(false);
                    if (response.status < 200 || response.status >= 300) {
                        setResult({ok: false, status: response.status, text: response.text || 'Request failed', authorized: []});

                        return;
                    }

                    const outcome = authorizeFromBody(system, operation.mappings, response.body);
                    if (0 === outcome.authorized.length) {
                        setResult({
                            ok: false,
                            status: response.status,
                            text: 'No token found in the response at "' + outcome.missing.join('", "') + '".',
                            authorized: [],
                        });

                        return;
                    }

                    setResult({ok: true, status: response.status, text: '', authorized: outcome.authorized});
                });
            };

            const logoutAll = function () {
                operation.mappings.forEach(function (mapping) {
                    logout(system, mapping);
                });
                setResult(null);
            };

            const renderField = function (field) {
                const id = 'api-platform-login-' + field.name;
                let input;

                if (field.enum) {
                    input = React.createElement('select', {
                        id: id,
                        value: values[field.name],
                        onChange: function (event) {
                            setValues(Object.assign({}, values, {[field.name]: event.target.value}));
                        },
                    }, [React.createElement('option', {key: '', value: ''}, '--')].concat(field.enum.map(function (option) {
                        return React.createElement('option', {key: String(option), value: String(option)}, String(option));
                    })));
                } else if ('boolean' === field.type) {
                    input = React.createElement('input', {
                        id: id,
                        type: 'checkbox',
                        checked: Boolean(values[field.name]),
                        onChange: function (event) {
                            setValues(Object.assign({}, values, {[field.name]: event.target.checked}));
                        },
                    });
                } else {
                    input = React.createElement('input', {
                        id: id,
                        type: field.isPassword ? 'password' : ('string' === field.type ? 'text' : 'number'),
                        name: field.name,
                        required: field.required,
                        autoComplete: field.isPassword ? 'current-password' : 'username',
                        value: values[field.name],
                        onChange: function (event) {
                            setValues(Object.assign({}, values, {[field.name]: event.target.value}));
                        },
                    });
                }

                return React.createElement('div', {key: field.name, className: 'wrapper api-platform-login-field'},
                    React.createElement('label', {htmlFor: id}, field.name, field.required ? React.createElement('span', {className: 'required'}, ' *') : null),
                    input
                );
            };

            const form = operation.form.fields
                ? operation.form.fields.map(renderField)
                : React.createElement('div', {className: 'wrapper api-platform-login-field'},
                    React.createElement('label', {htmlFor: 'api-platform-login-json'}, 'Request body (' + operation.form.contentType + ')'),
                    React.createElement('textarea', {
                        id: 'api-platform-login-json',
                        rows: 8,
                        value: jsonText,
                        onChange: function (event) {
                            setJsonText(event.target.value);
                        },
                    })
                );

            let resultBlock = null;
            if (result && result.ok) {
                resultBlock = React.createElement('div', {className: 'api-platform-login-result success'},
                    React.createElement('h6', null, 'Authorized (' + result.authorized.join(', ') + ')'),
                    React.createElement('div', {className: 'auth-btn-wrapper'},
                        React.createElement('button', {type: 'button', className: 'btn modal-btn auth authorize api-platform-login-logout', onClick: logoutAll}, 'Logout')
                    )
                );
            } else if (result) {
                resultBlock = React.createElement('div', {className: 'api-platform-login-result error'},
                    React.createElement('h6', null, result.status ? 'Login failed (HTTP ' + result.status + ')' : 'Login failed'),
                    result.text ? React.createElement('pre', null, String(result.text).slice(0, 2000)) : null
                );
            }

            return React.createElement('div', {className: 'dialog-ux api-platform-login-dialog'},
                React.createElement('div', {className: 'backdrop-ux', onClick: props.onClose}),
                React.createElement('div', {className: 'modal-ux'},
                    React.createElement('div', {className: 'modal-dialog-ux'},
                        React.createElement('div', {className: 'modal-ux-inner'},
                            React.createElement('div', {className: 'modal-ux-header'},
                                React.createElement('h3', null, 'Login'),
                                React.createElement('button', {type: 'button', className: 'close-modal', onClick: props.onClose}, React.createElement(CloseIcon, null))
                            ),
                            React.createElement('div', {className: 'modal-ux-content'},
                                React.createElement('div', {className: 'auth-container'},
                                    React.createElement('form', {className: 'api-platform-login-form', onSubmit: submit},
                                        operations.length > 1 ? React.createElement('div', {className: 'wrapper api-platform-login-field'},
                                            React.createElement('label', {htmlFor: 'api-platform-login-operation'}, 'Login operation'),
                                            React.createElement('select', {
                                                id: 'api-platform-login-operation',
                                                value: index,
                                                onChange: function (event) {
                                                    selectOperation(Number(event.target.value));
                                                },
                                            }, operations.map(function (candidate, candidateIndex) {
                                                return React.createElement('option', {key: candidateIndex, value: candidateIndex}, candidate.label);
                                            }))
                                        ) : null,
                                        React.createElement('h4', null, React.createElement('code', null, operation.method.toUpperCase() + ' ' + operation.path)),
                                        form,
                                        resultBlock,
                                        React.createElement('div', {className: 'auth-btn-wrapper'},
                                            React.createElement('button', {type: 'submit', className: 'btn modal-btn auth authorize api-platform-login-submit', disabled: submitting}, submitting ? 'Logging in…' : 'Login'),
                                            React.createElement('button', {type: 'button', className: 'btn modal-btn auth btn-done', onClick: props.onClose}, 'Close')
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            );
        }

        return {
            statePlugins: {
                spec: {
                    wrapActions: {
                        updateJsonSpec: function (originalAction, wrappedSystem) {
                            return function (spec) {
                                return originalAction(disableSecurityOnLoginOperations(spec, normalizeConfig(wrappedSystem.getConfigs())));
                            };
                        },
                    },
                },
            },

            wrapComponents: {
                authorizeBtn: function (Original, wrappedSystem) {
                    return function (props) {
                        const original = React.createElement(Original, props);
                        const config = normalizeConfig(wrappedSystem.getConfigs());

                        const openState = React.useState(false);
                        const open = openState[0];
                        const setOpen = openState[1];
                        const specJson = wrappedSystem.specSelectors.specJson();
                        const operations = React.useMemo(function () {
                            return config.dialog ? collectLoginOperations(wrappedSystem) : [];
                        }, [specJson, config.dialog]);

                        if (!config.dialog || 0 === operations.length) {
                            return original;
                        }

                        // Hidden while logged in through one of the login schemes (AuthorizeBtnContainer re-renders on auth changes)
                        const authorized = wrappedSystem.authSelectors.authorized();
                        const loggedIn = operations.some(function (operation) {
                            return operation.mappings.some(function (mapping) {
                                return Boolean(authorized && authorized.get(mapping.securityScheme));
                            });
                        });

                        return React.createElement('div', {className: 'api-platform-login-bar'},
                            original,
                            loggedIn ? null : React.createElement('button', {
                                type: 'button',
                                className: 'btn authorize unlocked api-platform-login-open',
                                onClick: function () {
                                    setOpen(true);
                                },
                            },
                                React.createElement('span', null, 'Login'),
                                React.createElement('svg', {width: 20, height: 20, viewBox: '0 0 20 20', 'aria-hidden': true, focusable: false},
                                    React.createElement('path', {d: 'M10 1.5a4.25 4.25 0 1 1 0 8.5 4.25 4.25 0 0 1 0-8.5zm0 10c4.28 0 7.75 2.13 7.75 4.75V18.5H2.25v-2.25c0-2.62 3.47-4.75 7.75-4.75z'})
                                )
                            ),
                            open ? React.createElement(LoginDialog, {
                                operations: operations,
                                getComponent: props.getComponent,
                                onClose: function () {
                                    setOpen(false);
                                },
                            }) : null
                        );
                    };
                },

                liveResponse: function (Original, wrappedSystem) {
                    return function (props) {
                        const original = React.createElement(Original, props);
                        const response = props.response;

                        if (!normalizeConfig(wrappedSystem.getConfigs()).responseButton || !response || typeof response.get !== 'function') {
                            return original;
                        }

                        const status = Number(response.get('status'));
                        if (!(status >= 200 && status < 300)) {
                            return original;
                        }

                        const mappings = resolveMappings(wrappedSystem, props.path, props.method);
                        if (0 === mappings.length) {
                            return original;
                        }

                        const body = parseBody(response);
                        if (!body) {
                            return original;
                        }

                        const entries = [];
                        mappings.forEach(function (mapping) {
                            const token = getByPath(body, mapping.tokenPath);
                            if (typeof token === 'string' && '' !== token) {
                                entries.push({mapping: mapping, token: token});
                            }
                        });

                        if (0 === entries.length) {
                            return original;
                        }

                        return React.createElement(LoginContext.Provider, {value: entries}, original);
                    };
                },

                responseBody: function (Original) {
                    return function (props) {
                        const original = React.createElement(Original, props);
                        const entries = React.useContext(LoginContext);

                        if (!entries || 0 === entries.length) {
                            return original;
                        }

                        const buttons = entries.map(function (entry, index) {
                            return React.createElement(LoginButton, {
                                key: entry.mapping.securityScheme + '-' + index,
                                mapping: entry.mapping,
                                token: entry.token,
                            });
                        });

                        // Rendered on top of the response body, next to the "Download" and "Copy" buttons
                        return React.createElement('div', {className: 'api-platform-login-response'},
                            original,
                            React.createElement('div', {className: 'api-platform-login-actions'}, buttons)
                        );
                    };
                },
            },
        };
    };
})();
