import { test, Page } from '@playwright/test';
import { VendorShippingPage } from '@pages/vendorShippingPage';
// import { ApiUtils } from '@utils/apiUtils';
import { data } from '@utils/testData';
// import { payloads } from '@utils/payloads';

test.describe('Vendor shipping test', () => {
    let vendor: VendorShippingPage;
    let vPage: Page;
    // let apiUtils: ApiUtils;

    test.beforeAll(async ({ browser }) => {
        const vendorContext = await browser.newContext(data.auth.vendorAuth);
        vPage = await vendorContext.newPage();
        vendor = new VendorShippingPage(vPage);

        // apiUtils = new ApiUtils(await request.newContext());
    });

    test.afterAll(async () => {
        await vPage.close();
        // await apiUtils.dispose();
    });

    test('vendor shipping settings menu page is rendering properly @pro @exp @v', async () => {
        await vendor.vendorShippingSettingsRenderProperly();
    });

    test('vendor can set shipping policy @pro @v', async () => {
        await vendor.setShippingPolicies(data.vendor.shipping.shippingPolicy);
    });

    test('vendor can add flat rate shipping @pro @v', async () => {
        await vendor.addShippingMethod(data.vendor.shipping.shippingMethods.flatRate);
    });

    test('vendor can add free shipping @pro @v', async () => {
        await vendor.addShippingMethod(data.vendor.shipping.shippingMethods.freeShipping);
    });

    test('vendor can add local pickup shipping @pro @v', async () => {
        await vendor.addShippingMethod(data.vendor.shipping.shippingMethods.localPickup);
    });

    test('vendor can add table rate shipping @pro @v', async () => {
        await vendor.addShippingMethod(data.vendor.shipping.shippingMethods.tableRateShipping);
    });

    test('vendor can add dokan distance rate shipping @pro @v', async () => {
        await vendor.addShippingMethod(data.vendor.shipping.shippingMethods.distanceRateShipping);
    });

    test('vendor can edit shipping method @pro @v', async () => {
        await vendor.addShippingMethod(data.vendor.shipping.shippingMethods.localPickup, false, true);
        await vendor.addShippingMethod(data.vendor.shipping.shippingMethods.localPickup);
    });

    test('vendor can delete shipping method @pro @v', async () => {
        await vendor.addShippingMethod(data.vendor.shipping.shippingMethods.flatRate, true, true); // todo: add with api v2 settings group
        await vendor.deleteShippingMethod(data.vendor.shipping.shippingMethods.flatRate);
    });
});
