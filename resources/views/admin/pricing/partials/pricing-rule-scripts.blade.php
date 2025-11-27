@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            const defaults = () => ({
                id: null,
                priority: 0,
                carrier: '',
                plating_carrier: '',
                marketing_carriers_rule: '{{ \App\Models\PricingRule::AIRLINE_RULE_NO_RESTRICTION }}',
                marketing_carriers_rule_label: '',
                operating_carriers_rule: '{{ \App\Models\PricingRule::AIRLINE_RULE_NO_RESTRICTION }}',
                operating_carriers_rule_label: '',
                marketing_carriers: '',
                operating_carriers: '',
                flight_restriction_type: '{{ \App\Models\PricingRule::FLIGHT_RESTRICTION_NONE }}',
                flight_restriction_type_label: '',
                flight_numbers: '',
                usage: '{{ \App\Models\PricingRule::USAGE_COMMISSION_BASE }}',
                origin: '',
                destination: '',
                both_ways: false,
                travel_oneway: true,
                travel_return: true,
                travel_type: 'OW+RT',
                cabin_class: '',
                booking_class_rbd: '',
                booking_class_usage: '{{ \App\Models\PricingRule::BOOKING_CLASS_USAGE_AT_LEAST_ONE }}',
                passenger_types: [],
                sales_since: '',
                sales_till: '',
                departures_since: '',
                departures_till: '',
                returns_since: '',
                returns_till: '',
                fare_type: 'public_and_private',
                promo_code: '',
                amount_mode: 'percent',
                percent: '',
                flat_amount: '',
                fee_percent: '',
                fixed_fee: '',
                is_primary_pcc: '0',
                active: true,
                notes: '',
            });

            const normalizeCarrierRule = (value) => value || '{{ \App\Models\PricingRule::AIRLINE_RULE_NO_RESTRICTION }}';
            const normalizeFlightRestriction = (value) => value || '{{ \App\Models\PricingRule::FLIGHT_RESTRICTION_NONE }}';

            const initStore = (config = {}) => {
                if (!Alpine.store('pricingRules')) {
                    Alpine.store('pricingRules', {
                        mode: 'create',
                        form: defaults(),
                        detail: defaults(),
                        passengerTypesText: '',
                        marketingCarriersText: '',
                        operatingCarriersText: '',
                        config,
                        defaults,
                        resetForm() {
                            this.form = defaults();
                            this.syncPassengerTypesText();
                            this.syncCarrierTexts();
                        },
                        normalizeCarrierRule,
                        normalizeFlightRestriction,
                        syncPassengerTypesText() {
                            this.passengerTypesText = (this.form.passenger_types || []).join(', ');
                        },
                        updatePassengerTypesFromText(text) {
                            if (typeof text !== 'string') {
                                this.form.passenger_types = [];
                                this.passengerTypesText = '';
                                return;
                            }

                            const parsed = text
                                .split(',')
                                .map((value) => value.trim().toUpperCase())
                                .filter((value) => value.length > 0)
                                .filter((value, index, array) => array.indexOf(value) === index);

                            this.form.passenger_types = parsed;
                            this.passengerTypesText = parsed.join(', ');
                        },
                        syncCarrierTexts() {
                            this.marketingCarriersText = Array.isArray(this.form.marketing_carriers)
                                ? this.form.marketing_carriers.join(', ')
                                : (this.form.marketing_carriers || '');
                            this.operatingCarriersText = Array.isArray(this.form.operating_carriers)
                                ? this.form.operating_carriers.join(', ')
                                : (this.form.operating_carriers || '');
                        },
                        updateCarrierListFromText(type, text) {
                            const list = (typeof text === 'string' ? text : '')
                                .split(',')
                                .map((value) => value.trim().toUpperCase())
                                .filter((value) => value.length > 0)
                                .filter((value, index, array) => array.indexOf(value) === index);

                            if (type === 'marketing') {
                                this.form.marketing_carriers = list;
                                this.marketingCarriersText = list.join(', ');
                            }
                            if (type === 'operating') {
                                this.form.operating_carriers = list;
                                this.operatingCarriersText = list.join(', ');
                            }
                        },
                        openEdit(rule = {}) {
                            this.mode = 'edit';
                            this.form = Object.assign(defaults(), rule);
                            this.form.marketing_carriers_rule = this.normalizeCarrierRule(this.form.marketing_carriers_rule);
                            this.form.operating_carriers_rule = this.normalizeCarrierRule(this.form.operating_carriers_rule);
                            this.form.flight_restriction_type = this.normalizeFlightRestriction(this.form.flight_restriction_type);
                            this.form.is_primary_pcc = this.form.is_primary_pcc ? '1' : '0';
                            this.form.amount_mode = (this.form.percent !== '' && this.form.percent !== null) ? 'percent' : 'flat';
                            this.form.travel_oneway = this.form.travel_type === 'OW' || this.form.travel_type === 'OW+RT';
                            this.form.travel_return = this.form.travel_type === 'RT' || this.form.travel_type === 'OW+RT';
                            this.syncPassengerTypesText();
                            this.syncCarrierTexts();
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pricing-rule-modal' }));
                        },
                        openCopy(rule = {}) {
                            this.mode = 'create';
                            const data = Object.assign(defaults(), rule);
                            data.id = null;
                            data.marketing_carriers_rule = this.normalizeCarrierRule(data.marketing_carriers_rule);
                            data.operating_carriers_rule = this.normalizeCarrierRule(data.operating_carriers_rule);
                            data.flight_restriction_type = this.normalizeFlightRestriction(data.flight_restriction_type);
                            data.is_primary_pcc = data.is_primary_pcc ? '1' : '0';
                            data.amount_mode = (data.percent !== '' && data.percent !== null) ? 'percent' : 'flat';
                            data.travel_oneway = data.travel_type === 'OW' || data.travel_type === 'OW+RT';
                            data.travel_return = data.travel_type === 'RT' || data.travel_type === 'OW+RT';
                            this.form = data;
                            this.syncPassengerTypesText();
                            this.syncCarrierTexts();
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pricing-rule-modal' }));
                        },
                        openDetail(rule = {}) {
                            this.detail = Object.assign(defaults(), rule);
                            this.detail.marketing_carriers_rule = this.normalizeCarrierRule(this.detail.marketing_carriers_rule);
                            this.detail.operating_carriers_rule = this.normalizeCarrierRule(this.detail.operating_carriers_rule);
                            this.detail.flight_restriction_type = this.normalizeFlightRestriction(this.detail.flight_restriction_type);
                            this.detail.marketing_carriers_list = Array.isArray(this.detail.marketing_carriers) ? this.detail.marketing_carriers.join(', ') : '';
                            this.detail.operating_carriers_list = Array.isArray(this.detail.operating_carriers) ? this.detail.operating_carriers.join(', ') : '';
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pricing-rule-detail' }));
                        },
                        formAction() {
                            if (this.mode === 'edit' && this.form.id) {
                                return `${this.config.updateBaseUrl}/${this.form.id}`;
                            }

                            return this.config.createUrl;
                        },
                        modeTitle() {
                            if (this.mode === 'edit' && this.form.id) {
                                return `Edit rule #${this.form.id}`;
                            }

                            return 'Create pricing rule';
                        },
                        updateTravelType() {
                            const oneway = !!this.form.travel_oneway;
                            const rt = !!this.form.travel_return;

                            if (oneway && rt) {
                                this.form.travel_type = 'OW+RT';
                            } else if (oneway) {
                                this.form.travel_type = 'OW';
                            } else if (rt) {
                                this.form.travel_type = 'RT';
                            } else {
                                this.form.travel_type = '';
                            }
                        },
                        syncAmountMode() {
                            if (this.form.amount_mode === 'percent') {
                                this.form.flat_amount = '';
                            } else if (this.form.amount_mode === 'flat') {
                                this.form.percent = '';
                            }
                        },
                    });
                }

                const store = Alpine.store('pricingRules');
                store.config = config;

                return store;
            };

            Alpine.data('pricingRulesPage', (config) => ({
                init() {
                    initStore(config);
                },
                store() {
                    return Alpine.store('pricingRules');
                },
                openEdit(rule) {
                    this.store().openEdit(rule);
                },
                openCopy(rule) {
                    this.store().openCopy(rule);
                },
                openDetail(rule) {
                    this.store().openDetail(rule);
                },
            }));

            Alpine.data('pricingRuleCreatePage', (config) => ({
                init() {
                    const store = initStore(config);
                    store.mode = 'create';
                    store.resetForm();
                },
                store() {
                    return Alpine.store('pricingRules');
                },
            }));
        });
    </script>
@endpush
