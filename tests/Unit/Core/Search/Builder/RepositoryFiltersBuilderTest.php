<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace Tests\Unit\Core\Search\Builder;

use PHPUnit\Framework\TestCase;
use PrestaShop\PrestaShop\Core\Employee\ContextEmployeeProviderInterface;
use PrestaShop\PrestaShop\Core\Search\Builder\RepositoryFiltersBuilder;
use PrestaShop\PrestaShop\Core\Search\Filters;
use PrestaShopBundle\Entity\AdminFilter;
use PrestaShopBundle\Entity\Repository\AdminFilterRepository;
use Symfony\Component\HttpFoundation\Request;

class RepositoryFiltersBuilderTest extends TestCase
{
    public function testBuildWithoutParameters()
    {
        /** @var AdminFilterRepository $repositoryMock */
        $repositoryMock = $this->getMockBuilder(AdminFilterRepository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        /** @var ContextEmployeeProviderInterface $employeeProviderMock */
        $employeeProviderMock = $this->getMockBuilder(ContextEmployeeProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $builder = new RepositoryFiltersBuilder($repositoryMock, $employeeProviderMock, 0);
        $filters = $builder->buildFilters();
        $this->assertNull($filters);
    }

    public function testBuildWithFilterId()
    {
        $expectedFilters = [
            'limit' => 10,
            'offset' => 10,
        ];
        $builder = new RepositoryFiltersBuilder(
            $this->buildRepositoryByFilterIdMock($expectedFilters, 'language'),
            $this->buildEmployeeProviderMock(),
            1
        );
        $builder->setConfig([
            'filter_id' => 'language',
        ]);
        $filters = $builder->buildFilters();
        $this->assertNotNull($filters);
        $this->assertEquals($expectedFilters, $filters->all());
        $this->assertEquals('language', $filters->getFilterId());
    }

    public function testOverrideWithFilterId()
    {
        $repositoryFilters = [
            'limit' => 10,
            'offset' => 10,
        ];
        $builder = new RepositoryFiltersBuilder(
            $this->buildRepositoryByFilterIdMock($repositoryFilters, 'alternate_language'),
            $this->buildEmployeeProviderMock(),
            1
        );
        $builder->setConfig([
            'filter_id' => 'language',
        ]);
        $filters = new Filters(['limit' => 20, 'orderBy' => 'language_id'], 'alternate_language');
        $builtFilters = $builder->buildFilters($filters);
        $this->assertNotNull($builtFilters);
        $expectedFilters = [
            'limit' => 10,
            'offset' => 10,
            'orderBy' => 'language_id',
        ];
        $this->assertEquals($expectedFilters, $builtFilters->all());
        $this->assertEquals('alternate_language', $filters->getFilterId());
    }

    public function testBuildWithController()
    {
        $expectedFilters = [
            'limit' => 10,
            'offset' => 10,
        ];
        $builder = new RepositoryFiltersBuilder(
            $this->buildRepositoryByRouteMock($expectedFilters, 'language', 'index'),
            $this->buildEmployeeProviderMock(),
            1
        );
        $builder->setConfig([
            'controller' => 'language',
            'action' => 'index',
        ]);
        $filters = $builder->buildFilters();
        $this->assertNotNull($filters);
        $this->assertEquals($expectedFilters, $filters->all());
        $this->assertEmpty($filters->getFilterId());
    }

    public function testOverrideWithController()
    {
        $repositoryFilters = [
            'limit' => 10,
            'offset' => 10,
        ];
        $builder = new RepositoryFiltersBuilder(
            $this->buildRepositoryByRouteMock($repositoryFilters, 'language', 'index'),
            $this->buildEmployeeProviderMock(),
            1
        );
        $builder->setConfig([
            'controller' => 'language',
            'action' => 'index',
        ]);
        $filters = new Filters(['limit' => 20, 'orderBy' => 'language_id']);
        $builtFilters = $builder->buildFilters($filters);
        $this->assertNotNull($builtFilters);
        $expectedFilters = [
            'limit' => 10,
            'offset' => 10,
            'orderBy' => 'language_id',
        ];
        $this->assertEquals($expectedFilters, $builtFilters->all());
        $this->assertEmpty($builtFilters->getFilterId());
    }

    public function testBuildWithRequest()
    {
        $expectedFilters = [
            'limit' => 10,
            'offset' => 10,
        ];
        $builder = new RepositoryFiltersBuilder(
            $this->buildRepositoryByRouteMock($expectedFilters, 'language', 'index'),
            $this->buildEmployeeProviderMock(),
            1
        );
        $builder->setConfig([
            'request' => $this->buildRequestMock('PrestaShopBundle\Controller\Admin\Improve\International\LanguageController::indexAction'),
        ]);
        $filters = $builder->buildFilters();
        $this->assertNotNull($filters);
        $this->assertEquals($expectedFilters, $filters->all());
        $this->assertEmpty($filters->getFilterId());
    }

    /**
     * @param array $filters
     * @param string $filterId
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AdminFilterRepository
     */
    private function buildRepositoryByFilterIdMock(array $filters, $filterId)
    {
        $repositoryMock = $this->getMockBuilder(AdminFilterRepository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $adminFilterMock = $this->buildAdminFilterMock($filters);

        $repositoryMock
            ->expects($this->once())
            ->method('findByEmployeeAndFilterId')
            ->with(
                $this->equalTo(1),
                $this->equalTo(1),
                $this->equalTo($filterId)
            )
            ->willReturn($adminFilterMock)
        ;

        $repositoryMock
            ->expects($this->never())
            ->method('findByEmployeeAndRouteParams')
        ;

        return $repositoryMock;
    }

    /**
     * @param array $filters
     * @param string $controller
     * @param string $action
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AdminFilterRepository
     */
    private function buildRepositoryByRouteMock(array $filters, $controller, $action)
    {
        $repositoryMock = $this->getMockBuilder(AdminFilterRepository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $adminFilterMock = $this->buildAdminFilterMock($filters);

        $repositoryMock
            ->expects($this->once())
            ->method('findByEmployeeAndRouteParams')
            ->with(
                $this->equalTo(1),
                $this->equalTo(1),
                $this->equalTo($controller),
                $this->equalTo($action)
            )
            ->willReturn($adminFilterMock)
        ;

        $repositoryMock
            ->expects($this->never())
            ->method('findByEmployeeAndFilterId')
        ;

        return $repositoryMock;
    }

    /**
     * @param array $filters
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AdminFilter
     */
    private function buildAdminFilterMock(array $filters)
    {
        $adminFilterMock = $this->getMockBuilder(AdminFilter::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $adminFilterMock
            ->expects($this->once())
            ->method('getFilter')
            ->willReturn(json_encode($filters))
        ;

        return $adminFilterMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ContextEmployeeProviderInterface
     */
    private function buildEmployeeProviderMock()
    {
        $employeeProviderMock = $this->getMockBuilder(ContextEmployeeProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $employeeProviderMock
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1)
        ;

        return $employeeProviderMock;
    }

    /**
     * @param string $controller
     *
     * @return Request
     */
    private function buildRequestMock($controller)
    {
        /** @var Request $requestMock */
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $requestMock
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('_controller')
            )
            ->willReturn($controller)
        ;

        return $requestMock;
    }
}
