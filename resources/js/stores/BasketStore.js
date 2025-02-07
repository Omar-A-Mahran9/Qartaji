import axios from "axios";
import { defineStore } from "pinia";
import { useToast } from "vue-toastification";
import AddToCartDialog from "../components/AddCartPopupDialog.vue";
import RemoveCartPopupDialog from "../components/RemoveCartPopupDialog.vue";

import { useAuth } from "./AuthStore";

const toast = useToast();

export const useBasketStore = defineStore("basketStore", {
    state: () => ({
        total: 0,
        products: [],
        checkoutProducts: [],
        selectedShopIds: [],
        total_amount: 0,
        delivery_charge: 0,
        coupon_discount: 0,
        payable_amount: 0,
        order_tax_amount: 0,
        coupon_code: "",
        showOrderConfirmModal: false,
        orderPaymentCancelModal: false,
        address: null,
        buyNowShopId: null,
        buyNowProduct: null,
        isLoadingCart: false,
    }),

    getters: {
        totalAmount: (state) => {
            let total = 0;
            state.products.forEach((item) => {
                item.products.forEach((product) => {
                    let price =
                        product.discount_price > 0
                            ? product.discount_price
                            : product.price;
                    total += price * product.quantity;
                });
            });
            return total.toFixed(2);
        },

        totalCheckoutAmount: (state) => {
            let total = 0;
            state.checkoutProducts.forEach((item) => {
                item.products.forEach((product) => {
                    let price =
                        product.discount_price > 0
                            ? product.discount_price
                            : product.price;
                    total += price * product.quantity;
                });
            });
            return total.toFixed(2);
        },

        checkoutTotalItems: (state) => {
            let total = 0;
            state.checkoutProducts.forEach((item) => {
                total += item.products.length;
            });
            return total;
        },
    },

    actions: {
        /**
         * Add a product to cart.
         * @param {object} data - object containing product id, quantity, color, size, unit.
         * @param {object} product - the product to add to cart.
         * @returns {Promise}
         */
        addToCart(data, product) {
            if (data.product_id) {
                this.isLoadingCart = true;
                const content = {
                    component: AddToCartDialog,
                    props: {
                        product: product,
                    },
                };
                const authStore = useAuth();
                axios
                    .post("/cart/store", data, {
                        headers: {
                            Authorization: authStore.token,
                        },
                    })
                    .then((response) => {
                        this.isLoadingCart = false;
                        if (!data.is_buy_now) {
                            this.total = response.data.data.total;
                            this.products = response.data.data.cart_items;
                            toast(content, {
                                type: "default",
                                hideProgressBar: true,
                                icon: false,
                                position: "bottom-left",
                                toastClassName: "vue-toastification-alert",
                                timeout: 3000,
                            });
                        }
                    })
                    .catch((error) => {
                        this.isLoadingCart = false;
                        if (error.response.status == 401) {
                            toast.error("Please login first!", {
                                position: "bottom-left",
                            });
                            const authStore = useAuth();
                            authStore.showLoginModal();
                        } else {
                            toast.error(error.response.data.message, {
                                position: "bottom-left",
                            });
                        }
                        return error;
                    });
            }
        },

        /**
         * Fetches the cart data from the server and updates the state.
         * If the user is not logged in, it clears the cart and related state.
         */
        fetchCart() {
            const authStore = useAuth();
            if (authStore.token) {
                axios
                    .get("/carts", {
                        headers: {
                            Authorization: authStore.token,
                        },
                    })
                    .then((response) => {
                        this.total = response.data.data.total;
                        this.products = response.data.data.cart_items;
                    })
                    .catch((error) => {
                        if (error.response.status === 401) {
                            authStore.token = null;
                            authStore.user = null;
                            authStore.addresses = [];
                        }
                    });
            } else {
                this.total = 0;
                this.products = [];
                this.checkoutProducts = [];
                this.selectedShopIds = [];
                this.total_amount = 0;
                this.delivery_charge = 0;
                this.coupon_discount = 0;
                this.payable_amount = 0;
                this.coupon_code = "";
                this.address = null;
                authStore.user = null;
                authStore.addresses = [];
                authStore.token = null;
            }
        },

        /**
         * Decrement the quantity of a given product in the cart
         * @param {object} product - the product to decrement the quantity for
         */
        decrementQuantity(product) {
            const authStore = useAuth();
            if (product) {
                const content = {
                    component: RemoveCartPopupDialog,
                    props: {
                        product: product,
                    },
                };
                axios
                    .post(
                        "/cart/decrement",
                        { product_id: product.id },
                        {
                            headers: {
                                Authorization: authStore.token,
                            },
                        }
                    )
                    .then((response) => {
                        this.total = response.data.data.total;
                        this.products = response.data.data.cart_items;
                        this.fetchCheckoutProducts();

                        if (
                            response.data.message == "product removed from cart"
                        ) {
                            const shopIds = this.products.map(
                                (shop) => shop.shop_id
                            );
                            this.selectedShopIds = this.selectedShopIds.filter(
                                (id) => shopIds.includes(id)
                            );
                            // const exists = shopIds.some((id) => selectedShopIds.includes(id));

                            if (this.products.length === 0) {
                                this.selectedShopIds = [];
                                this.checkoutProducts = [];
                                this.total_amount = 0;
                                this.delivery_charge = 0;
                                this.coupon_discount = 0;
                                this.payable_amount = 0;
                            }

                            toast(content, {
                                type: "default",
                                hideProgressBar: true,
                                icon: false,
                                position: "bottom-left",
                                toastClassName: "vue-toastification-alert",
                                timeout: 3000,
                            });
                        }
                    })
                    .catch((error) => {
                        if (error.response.status == 401) {
                            authStore.token = null;
                            authStore.user = null;
                            authStore.addresses = [];
                        }
                    });
            }
        },

        /**
         * Increment the quantity of the given product in the cart
         * @param {object} product - the product to increment the quantity for
         */
        incrementQuantity(product) {
            const authStore = useAuth();
            if (product) {
                axios.post("/cart/increment",
                    { product_id: product.id },
                    {
                        headers: {
                            Authorization: authStore.token,
                        },
                    }).then((response) => {
                    this.total = response.data.data.total;
                    this.products = response.data.data.cart_items;
                    this.fetchCheckoutProducts();
                }).catch((error) => {
                    toast.error(error.response.data.message, {
                        position: "bottom-left",
                    });
                    if (error.response.status == 401) {
                        authStore.token = null;
                        authStore.user = null;
                        authStore.addresses = [];
                    }
                });
            }
        },

        /**
         * Remove the given product from the cart
         * @param {object} product - the product to remove from the cart
         */
        removeFromBasket(product) {
            const authStore = useAuth();
            if (product) {
                axios.post(
                    "/cart/delete",
                    { product_id: product.id },
                    {
                        headers: {
                            Authorization: authStore.token,
                        },
                    }
                ).then((response) => {
                    this.total = response.data.data.total;
                    this.products = response.data.data.cart_items;
                    this.fetchCheckoutProducts();
                }).catch((error) => {
                    if (error.response.status == 401) {
                        authStore.token = null;
                        authStore.user = null;
                        authStore.addresses = [];
                    }
                });
            }
        },

        /**
         * Select or deselect the given shop for checkout
         * @param {number} shop - the shop to select or deselect
         */
        selectCartItemsForCheckout(shop) {
            if (!this.selectedShopIds.includes(shop)) {
                this.selectedShopIds.push(shop);
            } else {
                this.selectedShopIds = this.selectedShopIds.filter(
                    (item) => item !== shop
                );
            }
            this.fetchCheckoutProducts();
        },

        /**
         * Fetches the checkout products for the currently selected shops and updates
         * the checkout-related state, including total amount, delivery charge, coupon
         * discount, and payable amount. If the checkout products are empty, it clears
         * the selected shop IDs. Uses the auth token for authorization.
         */
        fetchCheckoutProducts() {
            const authStore = useAuth();
            if (authStore.token) {
                axios
                    .post(
                        "/cart/checkout",
                        {
                            shop_ids: this.selectedShopIds,
                        },
                        {
                            headers: {
                                Authorization: authStore.token,
                            },
                        }
                    )
                    .then((response) => {
                        this.checkoutProducts =
                            response.data.data.checkout_items;
                        this.total_amount =
                            response.data.data.checkout.total_amount;
                        this.delivery_charge =
                            response.data.data.checkout.delivery_charge;
                        this.coupon_discount =
                            response.data.data.checkout.coupon_discount;
                        this.payable_amount =
                            response.data.data.checkout.payable_amount;
                        this.order_tax_amount =
                            response.data.data.checkout.order_tax_amount;
                        if (this.checkoutProducts.length === 0) {
                            this.selectedShopIds = [];
                        }
                    })
                    .catch((error) => {
                        toast.error(error.response.data.message);
                    });
            }
        },

        checkShopIsSelected(shopId) {
            return this.selectedShopIds.includes(shopId);
        },
    },

    persist: true,
});
