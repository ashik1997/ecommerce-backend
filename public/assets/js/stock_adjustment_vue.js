const stockAdjustmentApp = new Vue({
    el: '#stockAdjustmentApp',
    data: {
        selectedProduct: null,
        adjustmentType: '',
        description: '',
        singleQuantity: 0,
        singleBarcodes: [],
        singleBarcodeEntry: '',
        commonVariantQty: 0,
        isSubmitting: false,
        // Variant selection & barcode state
        variantSelections: {},
        matchedVariant: null,
        variantQty: 0,
        variantBarcodes: [],
        barcodeEntry: ''
    },
    mounted() {
        this.initializeSelect2();
    },
    methods: {
        /**
         * Initialize Select2 for product search
         */
        initializeSelect2() {
            const self = this;
            
            $('#productSelect').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search by name, code, SKU, or barcode...',
                allowClear: true,
                ajax: {
                    url: '/stock-adjustment/search-products',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.data.items,
                            pagination: data.pagination
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                templateResult: function(item) {
                    if (item.loading) {
                        return item.text;
                    }
                    
                    return $(`
                        <div class="select2-product-result">
                            <div><strong>${item.name}</strong></div>
                            <div class="text-muted small">
                                Code: ${item.code} | SKU: ${item.sku || 'N/A'} | Stock: ${item.stock}
                            </div>
                        </div>
                    `);
                },
                templateSelection: function(item) {
                    return item.name || item.text;
                }
            }).on('select2:select', function(e) {
                const productId = e.params.data.id;
                self.loadProductDetails(productId);
            }).on('select2:clear', function() {
                self.resetForm();
            });
        },

        /**
         * Load product details including variants
         */
        async loadProductDetails(productId) {
            try {
                // const response = await axios.get(`/stock-adjustment/product/${productId}`);
                const response = await axios.get(`/stock-adjustment/search-products?q=${productId}`);
                console.log(response.data.data.items[0]);

                if (response.data.success) {
                    this.selectedProduct = response.data.data.items[0];

                    // Reset form fields
                    this.adjustmentType = '';
                    this.description = '';
                    this.singleQuantity = 0;
                    this.singleBarcodes = [];
                    this.singleBarcodeEntry = '';
                    this.commonVariantQty = 0;
                    this.resetVariantState();
                    
                    toastr.success('Product loaded successfully!', 'Success');
                } else {
                    toastr.error(response.data.message || 'Failed to load product details');
                }
            } catch (error) {
                console.error('Error loading product:', error);
                toastr.error('Error loading product details');
            }
        },

        /**
         * Calculate new stock for single product
         */
        calculateNewStock() {
            if (!this.selectedProduct || !this.adjustmentType || !this.singleQuantity) {
                return this.selectedProduct ? this.selectedProduct.stock : 0;
            }

            const currentStock = parseInt(this.selectedProduct.stock) || 0;
            const qty = parseInt(this.singleQuantity) || 0;
            
            // Determine if type adds or subtracts
            const subtractTypes = ['sales', 'waste', 'transfer'];
            const newStock = subtractTypes.includes(this.adjustmentType) 
                ? currentStock - qty 
                : currentStock + qty;
            
            return Math.max(0, newStock);
        },

        /**
         * Get variant attribute keys from selectedProduct.product_variants
         */
        getVariantAttributes() {
            if (!this.selectedProduct || !this.selectedProduct.product_variants) {
                return [];
            }

            return Object.keys(this.selectedProduct.product_variants);
        },

        /**
         * When a variant attribute is changed
         */
        onVariantSelectionChange() {
            this.matchedVariant = this.findMatchedVariant();
            this.variantQty = 0;
            this.variantBarcodes = [];
        },

        /**
         * Find matching combination from product_variant_combinations
         */
        findMatchedVariant() {
            if (!this.selectedProduct || !this.selectedProduct.product_variant_combinations) {
                return null;
            }

            const selectedAttrs = Object.keys(this.variantSelections || {}).filter(
                key => this.variantSelections[key]
            );

            // Need at least one selected attribute to attempt a match
            if (!selectedAttrs.length) {
                return null;
            }

            let matchVariant =  this.selectedProduct.product_variant_combinations.find(comb => {
                return selectedAttrs.every(attr => comb[attr] === this.variantSelections[attr]);
            }) || null;

            // for (const key in matchVariant) {
            //     if (Object.prototype.hasOwnProperty.call(matchVariant, key)) {
            //         const element = matchVariant[key];
            //         if(key != 'id'){
            //             this.variantSelections[key] = element;
            //         }
            //     }
            // }

            return matchVariant;
        },

        /**
         * Human readable label for current combination
         */
        getCombinationLabel() {
            const attrs = this.getVariantAttributes();
            if (!attrs.length) {
                return '';
            }

            return attrs
                .filter(attr => this.variantSelections[attr])
                .map(attr => `${attr}: ${this.variantSelections[attr]}`)
                .join(' | ');
        },

        /**
         * Single product: on Enter in "Enter Barcode" field - add barcode, increment qty, clear input
         */
        addSingleBarcodeFromEntry() {
            const trimmed = (this.singleBarcodeEntry || '').toString().trim();
            if (!trimmed) {
                return;
            }
            this.singleBarcodes.push(trimmed);
            this.singleQuantity = this.singleBarcodes.length;
            this.singleBarcodeEntry = '';
        },

        /**
         * Single product: sync barcode array length with quantity
         */
        onSingleQtyChange() {
            let qty = parseInt(this.singleQuantity) || 0;
            if (qty < 0) {
                qty = 0;
            }
            this.singleQuantity = qty;
            if (qty === 0) {
                this.singleBarcodes = [];
                return;
            }
            if (this.singleBarcodes.length < qty) {
                while (this.singleBarcodes.length < qty) {
                    this.singleBarcodes.push('');
                }
            } else if (this.singleBarcodes.length > qty) {
                this.singleBarcodes.splice(qty);
            }
        },

        /**
         * Single product: clear barcodes and quantity
         */
        clearSingleBarcodes() {
            this.singleBarcodes = [];
            this.singleQuantity = 0;
            this.singleBarcodeEntry = '';
        },

        /**
         * Single product: remove one barcode at index and decrement qty
         */
        removeSingleBarcode(index) {
            this.singleBarcodes.splice(index, 1);
            this.singleQuantity = this.singleBarcodes.length;
        },

        /**
         * On Enter in "Enter Barcode" field: add barcode to list, increment qty, clear input
         */
        addBarcodeFromEntry() {
            if (!this.matchedVariant) {
                toastr.warning('Please select a variant combination first');
                return;
            }
            const trimmed = (this.barcodeEntry || '').toString().trim();
            if (!trimmed) {
                return;
            }
            this.variantBarcodes.push(trimmed);
            this.variantQty = this.variantBarcodes.length;
            this.barcodeEntry = '';
        },

        /**
         * Handle quantity change for current variant combination
         */
        onVariantQtyChange() {
            event.preventDefault();
            
            let qty = parseInt(this.variantQty) || 0;
            if (qty < 0) {
                qty = 0;
            }
            this.variantQty = qty;

            // Adjust barcode inputs array length
            if (qty === 0) {
                this.variantBarcodes = [];
                return;
            }

            if (this.variantBarcodes.length < qty) {
                while (this.variantBarcodes.length < qty) {
                    this.variantBarcodes.push('');
                }
            } else if (this.variantBarcodes.length > qty) {
                this.variantBarcodes.splice(qty);
            }
        },

        /**
         * Generate a single auto barcode and apply to all barcode inputs
         */
        generateAutoBarcode() {
            if (!this.matchedVariant || !this.variantQty || this.variantQty <= 0) {
                toastr.warning('Please select a valid variant combination and quantity first');
                return;
            }

            const autoCode = 'V' + Date.now().toString() + Math.floor(Math.random() * 1000).toString();

            this.variantBarcodes = Array.from({ length: this.variantQty }, () => autoCode);
        },

        /**
         * Clear all barcode inputs
         */
        clearBarcodes() {
            this.variantBarcodes = this.variantBarcodes.map(() => '');
        },

        /**
         * Variant: remove one barcode at index and decrement qty
         */
        removeVariantBarcode(index) {
            this.variantBarcodes.splice(index, 1);
            this.variantQty = this.variantBarcodes.length;
        },

        /**
         * Reset variant-related state
         */
        resetVariantState() {
            this.variantSelections = {};
            this.matchedVariant = null;
            this.variantQty = 0;
            this.variantBarcodes = [];
            this.barcodeEntry = '';
        },

        /**
         * Calculate new stock for a variant
         */
        calculateVariantNewStock(variant) {
            if (!this.adjustmentType || !variant.adjustment_qty) {
                return variant.present_stock || 0;
            }

            const currentStock = parseInt(variant.present_stock) || 0;
            const qty = parseInt(variant.adjustment_qty) || 0;
            
            // Determine if type adds or subtracts
            const subtractTypes = ['sales', 'waste', 'transfer'];
            const newStock = subtractTypes.includes(this.adjustmentType) 
                ? currentStock - qty 
                : currentStock + qty;
            
            return Math.max(0, newStock);
        },

        /**
         * Set common quantity to all variants
         */
        setAllVariants() {
            if (!this.selectedProduct || !this.selectedProduct.has_variants) {
                return;
            }

            if (!this.commonVariantQty || this.commonVariantQty <= 0) {
                toastr.warning('Please enter a valid quantity in the "Common Quantity" field');
                return;
            }

            this.selectedProduct.variants.forEach(variant => {
                variant.adjustment_qty = this.commonVariantQty;
            });

            toastr.success(`Set ${this.commonVariantQty} to all variants`, 'Success');
        },

        /**
         * Validate form before submission
         */
        validateForm() {
            if (!this.selectedProduct) {
                toastr.error('Please select a product');
                return false;
            }

            if (!this.adjustmentType) {
                toastr.error('Please select adjustment type');
                return false;
            }

            if (!this.selectedProduct.has_variants) {
                // Single product validation (qty and barcodes must match)
                if (!this.singleQuantity || this.singleQuantity <= 0) {
                    toastr.error('Please enter a valid quantity');
                    return false;
                }
                if (this.singleBarcodes.length !== this.singleQuantity) {
                    toastr.error('Barcode count must match the quantity');
                    return false;
                }
                const hasEmptySingleBarcode = this.singleBarcodes.some(code => !code || !code.toString().trim());
                if (hasEmptySingleBarcode) {
                    toastr.error('Please enter or scan barcodes for all quantities');
                    return false;
                }
            } else {
                // Variant product validation (selection + quantity + barcodes)
                const attrs = this.getVariantAttributes();
                if (!attrs.length) {
                    toastr.error('This product has variants but no variant definitions are available');
                    return false;
                }

                const selectedAttrs = Object.keys(this.variantSelections || {}).filter(
                    key => this.variantSelections[key]
                );

                if (!selectedAttrs.length) {
                    toastr.error('Please select at least one variant attribute (e.g., color, size, etc.)');
                    return false;
                }

                if (!this.matchedVariant) {
                    toastr.error('No matching variant combination found for the selected attributes');
                    return false;
                }

                if (!this.variantQty || this.variantQty <= 0) {
                    toastr.error('Please enter a valid quantity for the selected variant combination');
                    return false;
                }

                if (this.variantBarcodes.length !== this.variantQty) {
                    toastr.error('Barcode count must match the quantity');
                    return false;
                }

                const hasEmptyBarcode = this.variantBarcodes.some(code => !code || !code.toString().trim());
                if (hasEmptyBarcode) {
                    toastr.error('Please enter or generate barcodes for all quantities');
                    return false;
                }
            }

            return true;
        },

        /**
         * Submit stock adjustment
         */
        async submitAdjustment() {
            if (!this.validateForm()) {
                return;
            }

            this.isSubmitting = true;

            try {
                const formData = {
                    product_id: this.selectedProduct.id,
                    type: this.adjustmentType,
                    description: this.description,
                    has_variants: this.selectedProduct.has_variants
                };

                if (this.selectedProduct.has_variants) {
                    // Send single selected variant combination data
                    formData.variants = [{
                        combination_id: this.matchedVariant ? this.matchedVariant.id : null,
                        attributes: this.variantSelections,
                        quantity: this.variantQty,
                        barcodes: this.variantBarcodes
                    }];
                } else {
                    // Send single quantity and barcodes
                    formData.quantity = this.singleQuantity;
                    formData.barcodes = this.singleBarcodes;
                }

                const response = await axios.post('/stock-adjustment/store', formData, {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.data.success) {
                    toastr.success(response.data.message || 'Stock adjustment created successfully!', 'Success', {
                        timeOut: 3000,
                        progressBar: true
                    });

                    // Reset form and redirect after delay
                    setTimeout(() => {
                        window.location.href = response.data.redirect || '/stock-adjustment';
                    }, 1500);
                } else {
                    toastr.error(response.data.message || 'Error creating stock adjustment');
                }

            } catch (error) {
                console.error('Error submitting adjustment:', error);
                
                if (error.response && error.response.data) {
                    const errors = error.response.data.errors;
                    if (errors) {
                        Object.keys(errors).forEach(key => {
                            toastr.error(errors[key][0]);
                        });
                    } else {
                        toastr.error(error.response.data.message || 'Error creating stock adjustment');
                    }
                } else {
                    toastr.error('An unexpected error occurred');
                }
            } finally {
                this.isSubmitting = false;
            }
        },

        /**
         * Reset form to initial state
         */
        resetForm() {
            this.selectedProduct = null;
            this.adjustmentType = '';
            this.description = '';
            this.singleQuantity = 0;
            this.singleBarcodes = [];
            this.singleBarcodeEntry = '';
            this.commonVariantQty = 0;
            this.resetVariantState();
            
            // Clear Select2
            $('#productSelect').val(null).trigger('change');
        }
    }
});

