<template>
    <div class="main-container pt-8 pb-12">

        <div class="text-slate-800 text-lg lg:text-3xl font-bold">{{ $t('Most Popular') }}</div>

        <!-- Products -->
        <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6  gap-3 sm:gap-6 items-start mt-6">
            <div v-for="product in products" :key="product.id" class="w-full">
                <ProductCard :product="product" />
            </div>
            <div v-if="products.length == 0" class="text-slate-950 text-xl font-medium leading-7">
                {{ $t('No Products Found') }}
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex justify-between items-center w-full mt-8  gap-4 flex-wrap">
            <div class="text-slate-800 text-base font-normal leading-normal">
                {{ $t('Showing') }} {{ (perPage * (currentPage - 1) + 1) }} to {{ (perPage * (currentPage - 1) + products.length) }}
                {{ $t('of') }} {{ totalProducts }} {{ $t('results') }}
            </div>
            <div>
                <vue-awesome-paginate :total-items="totalProducts" :items-per-page="perPage" type="button"
                    :max-pages-shown="5" v-model="currentPage" :hide-prev-next-when-ends="true" @click="onClickHandler" />
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import ProductCard from '../components/ProductCard.vue';


const currentPage = ref(1);
const perPage = ref(12);

const products = ref([]);
const totalProducts = ref(0);

onMounted(() => {
    fetchProducts();
    window.scrollTo(0, 0);
});

const onClickHandler = async (page) => {
    currentPage.value = page;
    fetchProducts();
};

const fetchProducts = async () => {
    axios.get('/products', { params: { page: currentPage.value, per_page: perPage.value, sort_type: 'popular' } }).then((response) => {
        totalProducts.value = response.data.data.total;
        products.value = response.data.data.products;
    })
};

</script>
