require('module-alias/register');

const {expect} = require('chai');

// Import utils
const helper = require('@utils/helpers');
const loginCommon = require('@commonTests/loginBO');

// Import pages
const LoginPage = require('@pages/BO/login');
const DashboardPage = require('@pages/BO/dashboard');
const ProductSettingsPage = require('@pages/BO/shopParameters/productSettings');
const ProductsPage = require('@pages/BO/catalog/products');
const AddProductPage = require('@pages/BO/catalog/products/add');

// Import test context
const testContext = require('@utils/testContext');

const baseContext = 'functional_BO_shopParameters_productSettings_productsStock_enableStockManagement';

let browserContext;
let page;

// Init objects needed
const init = async function () {
  return {
    loginPage: new LoginPage(page),
    dashboardPage: new DashboardPage(page),
    productSettingsPage: new ProductSettingsPage(page),
    productsPage: new ProductsPage(page),
    addProductPage: new AddProductPage(page),
  };
};

describe('Enable stock management', async () => {
  // before and after functions
  before(async function () {
    browserContext = await helper.createBrowserContext(this.browser);
    page = await helper.newTab(browserContext);

    this.pageObjects = await init();
  });

  after(async () => {
    await helper.closeBrowserContext(browserContext);
  });

  // Login into BO
  loginCommon.loginBO();

  const tests = [
    {args: {action: 'disable', enable: false, isQuantityVisible: false}},
    {args: {action: 'enable', enable: true, isQuantityVisible: true}},
  ];

  tests.forEach((test, index) => {
    it('should go to \'Shop parameters > Product Settings\' page', async function () {
      await testContext.addContextItem(this, 'testIdentifier', `goToProductSettingsPage_${index}`, baseContext);

      await this.pageObjects.dashboardPage.goToSubMenu(
        this.pageObjects.dashboardPage.shopParametersParentLink,
        this.pageObjects.dashboardPage.productSettingsLink,
      );

      const pageTitle = await this.pageObjects.productSettingsPage.getPageTitle();
      await expect(pageTitle).to.contains(this.pageObjects.productSettingsPage.pageTitle);
    });

    it(`should ${test.args.action} stock management`, async function () {
      await testContext.addContextItem(this, 'testIdentifier', `${test.args.action}StockManagement`, baseContext);

      const result = await this.pageObjects.productSettingsPage.setEnableStockManagementStatus(test.args.enable);
      await expect(result).to.contains(this.pageObjects.productSettingsPage.successfulUpdateMessage);
    });

    it('should go to \'Catalog > Products\' page', async function () {
      await testContext.addContextItem(this, 'testIdentifier', `goToProductsPage${index}`, baseContext);

      await this.pageObjects.productSettingsPage.goToSubMenu(
        this.pageObjects.productSettingsPage.catalogParentLink,
        this.pageObjects.productSettingsPage.productsLink,
      );

      const pageTitle = await this.pageObjects.productsPage.getPageTitle();
      await expect(pageTitle).to.contains(this.pageObjects.productsPage.pageTitle);
    });

    it('should go to create product page and check the existence of quantity input', async function () {
      await testContext.addContextItem(this, 'testIdentifier', `checkIsQuantityInput${test.args.action}`, baseContext);

      await this.pageObjects.productsPage.goToAddProductPage();
      const isVisible = await this.pageObjects.addProductPage.isQuantityInputVisible();
      await expect(isVisible).to.equal(test.args.isQuantityVisible);
    });
  });
});
