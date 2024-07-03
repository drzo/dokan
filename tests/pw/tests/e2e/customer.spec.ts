import { test, Page } from '@playwright/test';
import { LoginPage } from '@pages/loginPage';
import { CustomerPage } from '@pages/customerPage';
import { data } from '@utils/testData';

test.describe('Customer functionality test', () => {
    let customer: CustomerPage;
    let customer2: CustomerPage;
    let cPage: Page, c2Page: Page;

    test.beforeAll(async ({ browser }) => {
        const customerContext = await browser.newContext(data.auth.customerAuth);
        cPage = await customerContext.newPage();
        customer = new CustomerPage(cPage);

        const customer2Context = await browser.newContext(data.auth.customer2Auth);
        c2Page = await customer2Context.newPage();
        customer2 = new CustomerPage(c2Page);
    });

    test.afterAll(async () => {
        await cPage.close();
        await c2Page.close();
    });

    test('customer can register', { tag: ['@lite', '@customer'] }, async ({ page }) => {
        const customer = new CustomerPage(page);
        await customer.customerRegister(data.customer.customerInfo);
    });

    test('customer can login', { tag: ['@lite', '@customer'] }, async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.login(data.customer);
    });

    test('customer can logout', { tag: ['@lite', '@customer'] }, async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.login(data.customer);
        await loginPage.logout();
    });

    test('customer can become a vendor', { tag: ['@lite', '@customer'] }, async ({ page }) => {
        const customer = new CustomerPage(page);
        await customer.customerRegister(data.customer.customerInfo);
        await customer.customerBecomeVendor(data.customer.customerInfo);
    });

    test('customer can add billing details', { tag: ['@lite', '@customer'] }, async () => {
        await customer.addBillingAddress(data.customer.customerInfo.billing);
    });

    test('customer can add shipping details', { tag: ['@lite', '@customer'] }, async () => {
        await customer.addShippingAddress(data.customer.customerInfo.shipping);
    });

    test('customer can add customer details', { tag: ['@lite', '@customer'] }, async () => {
        await customer.addCustomerDetails(data.customer);
    });

    test('customer can add product to cart', { tag: ['@lite', '@customer'] }, async ({ page }) => {
        const customer = new CustomerPage(page); // Used guest customer to avoid conlict with other tests
        const productName = data.predefined.simpleProduct.product1.name;
        await customer.addProductToCart(productName, 'single-product');
        await customer.productIsOnCart(productName);
    });

    test('customer can buy product', { tag: ['@lite', '@customer'] }, async () => {
        await customer.addProductToCart(data.predefined.simpleProduct.product1.name, 'single-product');
        await customer.placeOrder();
    });

    test('customer can buy multi-vendor products', { tag: ['@lite', '@customer'] }, async () => {
        await customer2.addProductToCart(data.predefined.simpleProduct.product1.name, 'single-product');
        await customer2.addProductToCart(data.predefined.vendor2.simpleProduct.product1.name, 'single-product', false);
        await customer2.placeOrder();
    });

    // todo: customer can download downloadable product
});
