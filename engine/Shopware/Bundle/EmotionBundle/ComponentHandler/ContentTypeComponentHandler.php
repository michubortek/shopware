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

namespace Shopware\Bundle\EmotionBundle\ComponentHandler;

use Shopware\Bundle\ContentTypeBundle\Services\RepositoryInterface;
use Shopware\Bundle\ContentTypeBundle\Services\TypeProvider;
use Shopware\Bundle\ContentTypeBundle\Structs\Criteria;
use Shopware\Bundle\EmotionBundle\Struct\Collection\PrepareDataCollection;
use Shopware\Bundle\EmotionBundle\Struct\Collection\ResolvedDataCollection;
use Shopware\Bundle\EmotionBundle\Struct\Element;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentTypeComponentHandler implements ComponentHandlerInterface
{
    private const COMPONENT_NAME = 'emotion-components-content-type';

    private const MODE_NEWEST = 0;
    private const MODE_RANDOM = 1;
    private const MODE_SELECTED = 2;
    private const MODE_CONTENT_TYPE = 3;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var TypeProvider
     */
    private $typeProvider;

    public function __construct(ContainerInterface $container, TypeProvider $typeProvider)
    {
        $this->container = $container;
        $this->typeProvider = $typeProvider;
    }

    public function supports(Element $element): bool
    {
        return $element->getComponent()->getType() === self::COMPONENT_NAME;
    }

    public function prepare(PrepareDataCollection $collection, Element $element, ShopContextInterface $context): void
    {
        $mode = (int) $element->getConfig()->get('mode');

        if ($mode !== self::MODE_CONTENT_TYPE) {
            return;
        }

        $type = $this->typeProvider->getType($element->getConfig()->get('content_type'));
        $elementConfig = $element->getConfig();
        $elementConfig->set('sortField', $type->getSortField());
        $elementConfig->set('sortDirection', $type->getSortDirection());
    }

    public function handle(ResolvedDataCollection $collection, Element $element, ShopContextInterface $context): void
    {
        $elementConfig = $element->getConfig();

        /** @var RepositoryInterface $repository */
        $repository = $this->container->get('shopware.bundle.content_type.' . $elementConfig->get('content_type'));

        $mode = (int) $elementConfig->get('mode');

        $criteria = new Criteria();
        $criteria->limit = 5;
        $criteria->loadTranslations = true;
        $criteria->loadAssociations = true;

        if ($mode === self::MODE_NEWEST) {
            $criteria->sort = [
                [
                    'property' => 'id',
                    'direction' => 'DESC',
                ],
            ];
        } elseif ($mode === self::MODE_RANDOM) {
            $criteria->sort = [
                [
                    'property' => 'RANDOM',
                ],
            ];
        } elseif ($mode === self::MODE_SELECTED) {
            $criteria->filter = [
                [
                    'property' => 'id',
                    'value' => array_filter(explode('|', $elementConfig->get('ids'))),
                ],
            ];
        } elseif ($mode === self::MODE_CONTENT_TYPE) {
            $criteria->sort = [
                [
                    'property' => $elementConfig->get('sortField'),
                    'direction' => $elementConfig->get('sortDirection'),
                ],
            ];
        }

        $result = $repository->findAll($criteria);

        $element->getData()->set('sItems', $result->items);
        $element->getData()->set('sType', json_decode(json_encode($result->type), true));
    }
}
