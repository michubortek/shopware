<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Bundle\SearchBundle\CriteriaRequestHandler;

use Doctrine\DBAL\Connection;
use Enlight_Controller_Request_RequestHttp as Request;
use Shopware\Bundle\SearchBundle\Condition\PropertyCondition;
use Shopware\Bundle\SearchBundle\Condition\VariantCondition;
use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\SearchBundle\CriteriaRequestHandlerInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;

/**
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class VariantCriteriaRequestHandler implements CriteriaRequestHandlerInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param Request              $request
     * @param Criteria             $criteria
     * @param ShopContextInterface $context
     */
    public function handleRequest(Request $request, Criteria $criteria, ShopContextInterface $context)
    {
        $this->addVariantCondition($request, $criteria);
    }

    /**
     * @param Request  $request
     * @param Criteria $criteria
     */
    private function addVariantCondition(Request $request, Criteria $criteria)
    {
        $filters = $request->getParam('options', []);
        if (empty($filters)) {
            return;
        }

        $filters = explode('|', $filters);
        $filters = $this->getGroupedFilters($filters);

        if (empty($filters)) {
            return;
        }

        foreach ($filters as $filter) {
            $condition = new VariantCondition($filter);
            $criteria->addCondition($condition);
        }
    }

    /**
     * Helper function which groups the passed filter option ids
     * by the filter group.
     * Each filter group is joined as own PropertyCondition to the criteria
     * object
     *
     * @param $filters
     *
     * @return array
     */
    private function getGroupedFilters($filters)
    {
        $sql = "
            SELECT
                group_id,
                GROUP_CONCAT(variantOptions.id SEPARATOR '|') as optionIds
            FROM s_article_configurator_options variantOptions
            WHERE variantOptions.id IN (?)
            GROUP BY variantOptions.group_id
        ";

        $data = $this->connection->fetchAll(
            $sql,
            [$filters],
            [Connection::PARAM_INT_ARRAY]
        );

        $result = [];
        foreach ($data as $value) {
            $groupId = $value['group_id'];
            $optionIds = explode('|', $value['optionIds']);

            if (empty($optionIds)) {
                continue;
            }
            $result[$groupId] = $optionIds;
        }

        return $result;
    }
}
