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

use Shopware\Models\Order\Order as Order;
use Shopware\Models\Order\Billing as Billing;
use Shopware\Models\Order\Shipping as Shipping;
use Shopware\Models\Order\Detail as Detail;
use Shopware\Models\Order\Document\Document as Document;
use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;

/**
 * Backend Controller for the order backend module.
 *
 * Displays all orders in an Ext.grid.Panel and allows to delete,
 * add and edit orders.
 */
class Shopware_Controllers_Backend_Order extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Order repository. Declared for an fast access to the order repository.
     *
     * @var \Shopware\Models\Order\Repository
     * @access private
     */
    public static $repository = null;

    /**
     * Shop repository. Declared for an fast access to the shop repository.
     *
     * @var \Shopware\Models\Shop\Repository
     * @access private
     */
    public static $shopRepository = null;

    /**
     * Country repository. Declared for an fast access to the country repository.
     *
     * @var \Shopware\Models\Country\Repository
     * @access private
     */
    public static $countryRepository = null;

    /**
     * Payment repository. Declared for an fast access to the country repository.
     *
     * @var \Shopware\Models\Payment\Repository
     * @access private
     */
    public static $paymentRepository = null;

    /**
     * Contains the shopware model manager
     *
     * @var \Shopware\Components\Model\ModelManager
     * @access private
     */
    public static $manager = null;

    /**
     * Contains the dynamic receipt repository
     * @var \Shopware\Components\Model\ModelRepository
     */
    public static $documentRepository = null;

    /**
     * Returns the shopware model manager
     *
     * @return Shopware\Components\Model\ModelManager
     */
    protected function getManager()
    {
        if (self::$manager === null) {
            self::$manager = Shopware()->Models();
        }
        return self::$manager;
    }

    /**
     * Helper function to get access on the static declared repository
     *
     * @return null|Shopware\Models\Order\Repository
     */
    protected function getRepository()
    {
        if (self::$repository === null) {
            self::$repository = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
        }
        return self::$repository;
    }

    /**
     * Helper function to get access on the static declared repository
     *
     * @return null|Shopware\Models\Shop\Repository
     */
    protected function getShopRepository()
    {
        if (self::$shopRepository === null) {
            self::$shopRepository = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop');
        }
        return self::$shopRepository;
    }

    /**
     * Helper function to get access on the static declared repository
     *
     * @return null|Shopware\Models\Country\Repository
     */
    protected function getCountryRepository()
    {
        if (self::$countryRepository === null) {
            self::$countryRepository = Shopware()->Models()->getRepository('Shopware\Models\Country\Country');
        }
        return self::$countryRepository;
    }

    /**
     * Helper function to get access on the static declared repository
     *
     * @return null|Shopware\Models\Payment\Repository
     */
    protected function getPaymentRepository()
    {
        if (self::$paymentRepository === null) {
            self::$paymentRepository = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment');
        }
        return self::$paymentRepository;
    }

    /**
     * Helper function to get access on the static declared repository
     *
     * @return \Shopware\Components\Model\ModelRepository
     */
    protected function getDocumentRepository()
    {
        if (self::$documentRepository === null) {
            self::$documentRepository = Shopware()->Models()->getRepository('Shopware\Models\Order\Document\Document');
        }
        return self::$documentRepository;
    }

    /**
     * Registers the different acl permission for the different controller actions.
     *
     * @return void
     */
    protected function initAcl()
    {
        //        /** @var $namespace Enlight_Components_Snippet_Namespace */
//        $namespace = Shopware()->Snippets()->getNamespace('backend/customer');
//        $this->setAclResourceName('customer');
//        $this->addAclPermission('getListAction','read', $namespace->get('no_list_rights', 'You do not have sufficient rights to view the list of customers.'));
//        $this->addAclPermission('getDetailAction', 'detail', $namespace->get('no_detail_rights', 'You do not have sufficient rights to view the customer detail page.'));
//        $this->addAclPermission('getOrdersAction', 'read', $namespace->get('no_order_rights', 'You do not have sufficient rights to view customer orders.'));
//        $this->addAclPermission('getOrderChartAction', 'read', $namespace->get('no_order_rights', 'You do not have sufficient rights to view customer orders.'));
//        $this->addAclPermission('deleteAction', 'delete', $namespace->get('no_delete_rights', 'You do not have sufficient rights to delete a customers.'));
    }

    /**
     * Get a list of available payment status
     */
    public function getPaymentStatusAction()
    {
        $orderStatus = $this->getRepository()->getPaymentStatusQuery()->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data' => $orderStatus
        ));
    }

    /**
     * Enable json renderer for index / load action
     * Check acl rules
     *
     * @return void
     */
    public function preDispatch()
    {
        if (!in_array($this->Request()->getActionName(), array('index', 'load', 'skeleton', 'extends', 'orderPdf'))) {
            $this->Front()->Plugins()->Json()->setRenderer();
        }
    }

    /**
     *
     */
    public function loadListAction()
    {
        $filters        = array(array('property' => 'status.id','expression' => '!=','value' => '-1'));
        $orderStatus    = $this->getRepository()->getOrderStatusQuery($filters)->getArrayResult();
        $paymentStatus  = $this->getRepository()->getPaymentStatusQuery()->getArrayResult();
        $positionStatus = $this->getRepository()->getDetailStatusQuery()->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data' => array(
                'orderStatus' => $orderStatus,
                'paymentStatus' => $paymentStatus,
                'positionStatus' => $positionStatus
            )
        ));
    }

    /**
     * Get documents of a specific type for the given orders
     * @param $orders
     * @param $docType
     * @return \Doctrine\ORM\Query
     */
    public function getOrderDocumentsQuery($orderIds, $docType)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array(
            'orders',
            'documents',
        ));

        $builder->from('Shopware\Models\Order\Order', 'orders');
        $builder->leftJoin('orders.documents', 'documents')
                ->where('documents.typeId = :type')
                ->andWhere($builder->expr()->in('orders.id', $orderIds))
                ->setParameter(':type', $docType);
        return $builder->getQuery();
    }

    /**
     * This class has its own OrderStatusQuery as we need to get rid of states with satus.id = -1
     */
    public function getOrderStatusQuery($filter = null, $order = null, $offset = null, $limit = null)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('status'))
                    ->from('Shopware\Models\Order\Status', 'status')
                    ->andWhere("status.group = 'state'");

        if ($filter !== null) {
            $builder->addFilter($filter);
        }
        if ($order !== null) {
            $builder->addOrderBy($order);
        }

        if ($offset !== null) {
            $builder->setFirstResult($offset)
                    ->setMaxResults($limit);
        }

        return $builder->getQuery();
    }



    /**
     * batch function which summarizes some queries in order to speed up the order-detail startup
     */
    public function loadStoresAction()
    {
        $id = $this->Request()->getParam('orderId', null);
        if ($id === null) {
            $this->View()->assign(array('success' => false, 'message' => 'No orderId passed'));
            return;
        }

        $orderStatus = $this->getOrderStatusQuery()->getArrayResult();
        $paymentStatus = $this->getRepository()->getPaymentStatusQuery()->getArrayResult();
        $shops = $this->getShopRepository()->getBaseListQuery()->getArrayResult();
        $countries = $this->getCountryRepository()->getCountriesQuery()->getArrayResult();
        $payments = $this->getPaymentRepository()->getAllPaymentsQuery()->getArrayResult();
        $documentTypes = $this->getRepository()->getDocumentTypesQuery()->getArrayResult();

        $this->View()->assign(array(
            'success' => true,
            'data' => array(
                'orderStatus' => $orderStatus,
                'paymentStatus' => $paymentStatus,
                'shops' => $shops,
                'countries' => $countries,
                'payments' => $payments,
                'documentTypes' => $documentTypes,
            )
        ));
        return;
    }

    /**
     * Event listener method which fires when the order store is loaded. Returns an array of order data
     * which displayed in an Ext.grid.Panel. The order data contains all associations of an order (positions, shop, customer, ...).
     * The limit, filter and order parameter are used in the id query. The result of the id query are used
     * to filter the detailed query which created over the getListQuery function.
     */
    public function getListAction()
    {
        //read store parameter to filter and paginate the data.
        $limit = $this->Request()->getParam('limit', 20);
        $offset = $this->Request()->getParam('start', 0);
        $sort = $this->Request()->getParam('sort', null);
        $filter = $this->Request()->getParam('filter', null);
        $orderId = $this->Request()->getParam('orderID');

        if (!is_null($orderId)) {
            $orderIdFilter = array('property' => 'orders.id', 'value' => $orderId);
            if (!is_array($filter)) {
                $filter = array();
            }
            array_push($filter, $orderIdFilter);
        }
        $list = $this->getList($filter, $sort, $offset, $limit);
        $this->View()->assign($list);
    }

    /**
     * @param $filter
     * @param $sort
     * @param $offset
     * @param $limit
     * @return array
     */
    protected function getList($filter, $sort, $offset, $limit)
    {
        try {
            if (empty($sort)) {
                $sort = array(array('property' => 'orders.orderTime', 'direction' => 'DESC'));
            } else {
                $sort[0]['property'] = 'orders.' . $sort[0]['property'];
            }

            $query = $this->getRepository()->getBackendOrdersQuery($filter, $sort, $offset, $limit);

            $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

            $paginator = $this->getModelManager()->createPaginator($query);

            //returns the total count of the query
            $total = $paginator->count();

            //returns the customer data
            $orders = $paginator->getIterator()->getArrayCopy();

            foreach ($orders as $key => &$order) {
                $additionalOrderDataQuery = $this->getRepository()->getBackendAdditionalOrderDataQuery($order['number']);
                $additionalOrderData = $additionalOrderDataQuery->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

                $order = array_merge($order, $additionalOrderData);
                //we need to set the billing and shipping attributes to the first array level to load the data into a form panel
                //same for locale
                $order['billingAttribute'] = $order['billing']['attribute'];
                $order['shippingAttribute'] = $order['shipping']['attribute'];
                $order['locale']= $order['languageSubShop']['locale'];

                //Deprecated: use payment instance
                $order['debit'] = $order['customer']['debit'];

                unset($order['billing']['attribute']);
                unset($order['shipping']['attribute']);

                //find the instock of the article
                foreach ($order["details"] as &$orderDetail) {
                    $articleRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail');
                    $article = $articleRepository->findOneBy(array('number' => $orderDetail["articleNumber"]));
                    if ($article instanceof \Shopware\Models\Article\Detail) {
                        $orderDetail['inStock'] = $article->getInStock();
                    }
                }

                $orders[$key] = $order;
            }

            return array(
                'success' => true,
                'data' => $orders,
                'total' => $total
            );
        } catch (\Doctrine\ORM\ORMException $e) {
            return array(
                'success' => false,
                'data' => array(),
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Returns the order ids for the list query.
     * @param $id
     * @param $filter
     * @param $sort
     * @param $limit
     * @param $offset
     * @return array
     */
    private function getListIds($id, $filter, $sort, $limit, $offset)
    {
        if ($id === null) {
            //Doctrine has problems to limit queries with 1:n or n:m association, so first we
            //create an query which selects only the founded order ids for the passed list parameters.
            $idQuery = $this->getRepository()->getListIdsQuery($filter, $sort, $offset, $limit);
            $totalResult = Shopware()->Models()->getQueryCount($idQuery);
            $idResult = $idQuery->getArrayResult();

            //iterate id query result an create a one dimension array of ids
            $ids = array();
            foreach ($idResult as $id) {
                $ids[] = $id['id'];
            }
        } else {
            $ids = array($id);
            $totalResult = 1;
        }
        return array(
            'ids' => $ids,
            'totalResult' => $totalResult
        );
    }

    /**
     * Returns an array of all defined taxes. Used for the position grid combo box on the detail page of the backend order module.
     */
    public function getTaxAction()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $tax = $builder->select(array('tax'))
                        ->from('Shopware\Models\Tax\Tax', 'tax')
                        ->getQuery()
                        ->getArrayResult();

        $this->View()->assign(array('success' => true, 'data' => $tax));
    }

    /**
     * The getVouchers function is used by the extJs voucher store which used for a
     * combo box on the order detail page.
     * @return Array
     */
    public function getVouchersAction()
    {
        $vouchers = $this->getRepository()->getVoucherQuery()->getArrayResult();
        $this->View()->assign(array('success' => true, 'data' => $vouchers));
    }

    /**
     * Returns all supported document types. The data is used for the configuration panel.
     * @return Array
     */
    public function getDocumentTypesAction()
    {
        $types = $this->getRepository()->getDocumentTypesQuery()->getArrayResult();
        $this->View()->assign(array('success' => true, 'data' => $types));
    }


    /**
     * Event listener function of the history store in the order backend module.
     * Returns the status history of the passed order.
     * @return array
     */
    public function getStatusHistoryAction()
    {
        $orderId = $this->Request()->getParam('orderID', null);
        $limit = $this->Request()->getParam('limit', 20);
        $offset = $this->Request()->getParam('start', 0);
        $sort = $this->Request()->getParam('sort',  array(array('property' => 'history.changeDate', 'direction' => 'DESC')));

        /** @var $namespace Enlight_Components_Snippet_Namespace */
        $namespace = Shopware()->Snippets()->getNamespace('backend/order');

        //the backend order module have no function to create a new order so an order id must be passed.
        if (empty($orderId)) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => $namespace->get('no_order_id_passed', 'No valid order id passed.'))
            );
            return;
        }

        $history = $this->getRepository()
                        ->getOrderStatusHistoryListQuery($orderId, $sort, $offset, $limit)
                        ->getArrayResult();

        try {
            $this->View()->assign(array(
                'success' => true,
                'data' => $history
            ));
        } catch (\Doctrine\ORM\ORMException $e) {
            $this->View()->assign(array(
                'success' => false,
                'data' => array(),
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * CRUD function save and update of the order model.
     *
     * Event listener method of the backend/order/model/order.js model which
     * is used for the backend order module detail page to edit a single order.
     */
    public function saveAction()
    {
        $id = $this->Request()->getParam('id', null);

        /** @var $namespace Enlight_Components_Snippet_Namespace */
        $namespace = Shopware()->Snippets()->getNamespace('backend/order');

        try {
            //the backend order module have no function to create a new order so an order id must be passed.
            if (empty($id)) {
                $this->View()->assign(array(
                    'success' => false,
                    'data' => $this->Request()->getParams(),
                    'message' => $namespace->get('no_order_id_passed', 'No valid order id passed.'))
                );
                return;
            }

            $order = $this->getRepository()->find($id);

            //the backend order module have no function to create a new order so an order id must be passed.
            if (!($order instanceof Order)) {
                $this->View()->assign(array(
                    'success' => false,
                    'data' => $this->Request()->getParams(),
                    'message' => $namespace->get('no_order_id_passed', 'No valid order id passed.'))
                );
                return;
            }

            $billing  = $order->getBilling();
            $shipping = $order->getShipping();

            //check if the shipping and billing model already exist. If not create a new instance.
            if (!$shipping instanceof \Shopware\Models\Order\Shipping) {
                $shipping = new Shipping();
            }

            if (!$billing instanceof \Shopware\Models\Order\Billing) {
                $billing = new Billing();
            }
            //get all passed order data
            $data = $this->Request()->getParams();

            //prepares the associated data of an order.
            $data = $this->getAssociatedData($data, $order, $billing, $shipping);

            //before we can create the status mail, we need to save the order data. Otherwise
            //the status mail would be created with the old order status and amount.
            /**@var $order \Shopware\Models\Order\Order*/
            $statusBefore  = $order->getOrderStatus();
            $clearedBefore = $order->getPaymentStatus();
            $invoiceShippingBefore = $order->getInvoiceShipping();
            $invoiceShippingNetBefore = $order->getInvoiceShippingNet();

            if (!empty($data['clearedDate'])) {
                try {
                    $data['clearedDate'] = new \DateTime($data['clearedDate']);
                } catch (\Exception $e) {
                    $data['clearedDate'] = null;
                }
            }

            $order->fromArray($data);

            //check if the invoice shipping has been changed
            $invoiceShippingChanged = (bool) ($invoiceShippingBefore != $order->getInvoiceShipping());
            $invoiceShippingNetChanged = (bool) ($invoiceShippingNetBefore != $order->getInvoiceShippingNet());
            if ($invoiceShippingChanged || $invoiceShippingNetChanged) {
                //recalculate the new invoice amount
                $order->calculateInvoiceAmount();
            }

            Shopware()->Models()->flush();
            Shopware()->Models()->clear();
            $order = $this->getRepository()->find($id);

            //if the status has been changed an status mail is created.
            $mail = null;
            if ($order->getOrderStatus()->getId() !== $statusBefore->getId() || $order->getPaymentStatus()->getId() !== $clearedBefore->getId()) {
                if ($order->getOrderStatus()->getId() !== $statusBefore->getId()) {
                    $mail = $this->getMailForOrder($order->getId(), $order->getOrderStatus()->getId());
                } else {
                    $mail = $this->getMailForOrder($order->getId(), $order->getPaymentStatus()->getId());
                }
            }

            $data = $this->getOrder($order->getId());
            if (!empty($mail)) {
                $data['mail'] = $mail['data'];
            } else {
                $data['mail'] = null;
            }

            $this->View()->assign(array(
                'success' => true,
                'data' => $data
            ));
            return;
        } catch (\Doctrine\ORM\ORMException $e) {
            $this->View()->assign(array(
                'success' => false,
                'data' => array(),
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Helper function to select a single order.
     * @param $id
     * @return mixed
     */
    private function getOrder($id)
    {
        $query = $this->getRepository()->getOrdersQuery(array(array('property' => 'orders.id', 'value' => $id)), array());
        $data = $query->getArrayResult();
        return $data[0];
    }

    /**
     * Deletes a single order from the database.
     * Expects a single order id which placed in the parameter id
     */
    public function deleteAction()
    {
        /** @var $namespace Enlight_Components_Snippet_Namespace */
        $namespace = Shopware()->Snippets()->getNamespace('backend/order');

        try {
            //get posted customers
            $orderId = $this->Request()->getParam('id');

            if (empty($orderId) || !is_numeric($orderId)) {
                $this->View()->assign(array(
                    'success' => false,
                    'data' => $this->Request()->getParams(),
                    'message' => $namespace->get('no_order_id_passed', 'No valid order id passed.'))
                );
                return;
            }

            $entity = $this->getRepository()->find($orderId);
            $this->getManager()->remove($entity);

            //Performs all of the collected actions.
            $this->getManager()->flush();

            $this->View()->assign(['success' => true]);
        } catch (Exception $e) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => $e->getMessage())
            );
        }
    }

    /**
     * CRUD function save and update of the position store of the backend order module.
     * The function handles the update and insert routine of a single order position.
     * After the position has been added to the order, the order invoice amount will be recalculated.
     * The refreshed order will be assigned to the view to refresh the panels and grids.
     *
     * @return mixed
     */
    public function savePositionAction()
    {
        $id = $this->Request()->getParam('id', null);

        $orderId = $this->Request()->getParam('orderId', null);

        /** @var $namespace Enlight_Components_Snippet_Namespace */
        $namespace = Shopware()->Snippets()->getNamespace('backend/order');

        //check if an order id is passed. If no order id passed, return success false
        if (empty($orderId)) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => $namespace->get('no_order_id_passed', 'No valid order id passed.'))
            );
            return;
        }

        //find the order model. If no model founded, return success false
        $order = $this->getRepository()->find($orderId);
        if (empty($order)) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => $namespace->get('no_order_id_passed', 'No valid order id passed.'))
            );
            return;
        }

        try {
            //check if the passed position data is a new position or an existing position.
            if (empty($id)) {
                $position = new Detail();
                Shopware()->Models()->persist($position);
            } else {
                $detailRepository = Shopware()->Models()->getRepository('Shopware\Models\Order\Detail');
                $position = $detailRepository->find($id);
            }

            $data = $this->Request()->getParams();
            $data['number'] = $order->getNumber();

            $data = $this->getPositionAssociatedData($data);
            // If $data === null, the article was not found
            if ($data === null) {
                $this->View()->assign(array(
                    'success' => false,
                    'data' => array(),
                    'message' => 'The articlenumber "' . $this->Request()->getParam('articleNumber', '') . '" is not valid'
                ));
                return;
            }

            $data['attribute'] = $data['attribute'][0];
            $position->fromArray($data);
            $position->setOrder($order);

            Shopware()->Models()->flush();

            //If the passed data is a new position, the flush function will add the new id to the position model
            $data['id'] = $position->getId();


            //The position model will refresh the article stock, so the article stock
            //will be assigned to the view to refresh the grid or form panel.
            $articleRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail');
            $article = $articleRepository->findOneBy(array('number' => $position->getArticleNumber()));
            if ($article instanceof \Shopware\Models\Article\Detail) {
                $data['inStock'] = $article->getInStock();
            }
            $order = $this->getRepository()->find($order->getId());

            Shopware()->Models()->persist($order);
            Shopware()->Models()->flush();

            $invoiceAmount = $order->getInvoiceAmount();

            if ($position->getOrder() instanceof \Shopware\Models\Order\Order) {
                $invoiceAmount = $position->getOrder()->getInvoiceAmount();
            }

            $this->View()->assign(array(
                'success' => true,
                'data' => $data,
                'invoiceAmount' => $invoiceAmount
            ));
            return;
        } catch (\Doctrine\ORM\ORMException $e) {
            $this->View()->assign(array(
                'success' => false,
                'data' => array(),
                'message' => $e->getMessage()
            ));
        }
    }
    /**
     * Internal helper function to save the dynamic attributes of an article price.
     * @param $position
     * @param $attributeData
     * @return mixed
     */
    private function savePositionAttributes($position, $attributeData)
    {
        if (empty($attributeData)) {
            return;
        }
        if ($position->getId() > 0) {
            $builder = Shopware()->Models()->createQueryBuilder();
            $builder->select(array('attribute'))
                    ->from('Shopware\Models\Attribute\OrderDetail', 'attribute')
                    ->where('attribute.orderDetailId = ?1')
                    ->setParameter(1, $position->getId());

            $result = $builder->getQuery()->getOneOrNullResult();
            if (empty($result)) {
                $attributes = new \Shopware\Models\Attribute\OrderDetail();
            } else {
                $attributes = $result;
            }
        } else {
            $attributes = new \Shopware\Models\Attribute\OrderDetail();
        }
        $attributes->fromArray($attributeData);
        $attributes->setOrderDetail($position);
        $this->getManager()->persist($attributes);
    }


    /**
     * CRUD function delete of the position and list store of the backend order module.
     * The function can delete one or many order positions. After the positions has been deleted
     * the order invoice amount will be recalculated. The refreshed order will be assigned to the
     * view to refresh the panels and grids.
     *
     * @return mixed
     */
    public function deletePositionAction()
    {
        /** @var $namespace Enlight_Components_Snippet_Namespace */
        $namespace = Shopware()->Snippets()->getNamespace('backend/order');

        $positions = $this->Request()->getParam('positions', array(array('id' => $this->Request()->getParam('id'))));

        //check if any positions is passed.
        if (empty($positions)) {
            $this->View()->assign(array(
               'success' => false,
               'data' => $this->Request()->getParams(),
               'message' => $namespace->get('no_order_passed', 'No orders passed'))
            );
            return;
        }

        //if no order id passed it isn't possible to update the order amount, so we will cancel the position deletion here.
        $orderId = $this->Request()->getParam('orderID', null);

        if (empty($orderId)) {
            $this->View()->assign(array(
               'success' => false,
               'data' => $this->Request()->getParams(),
               'message' => $namespace->get('no_order_id_passed', 'No valid order id passed.'))
            );
            return;
        }

        try {
            foreach ($positions as $position) {
                if (empty($position['id'])) {
                    continue;
                }
                $model = Shopware()->Models()->find('Shopware\Models\Order\Detail', $position['id']);

                //check if the model was founded.
                if ($model instanceof \Shopware\Models\Order\Detail) {
                    Shopware()->Models()->remove($model);
                }
            }
            //after each model has been removed to executes the doctrine flush.
            Shopware()->Models()->flush();

            /**@var $order \Shopware\Models\Order\Order*/
            $order = $this->getRepository()->find($orderId);
            $order->calculateInvoiceAmount();

            Shopware()->Models()->flush();

            $data = $this->getOrder($order->getId());
            $this->View()->assign(array(
                'success' => true,
                'data' => $data
            ));
        } catch (\Doctrine\ORM\ORMException $e) {
            $this->View()->assign(array(
               'success' => false,
               'data' => $this->Request()->getParams(),
               'message' => $e->getMessage()
            ));
            return;
        }
    }

    /**
     * The batchProcessAction function handles the request of the batch window in order backend module.
     * It is responsible to create the order document for the passed parameters and updates the order
     * or|and payment status that passed for each order. If the order or payment status has been changed
     * the function will create for each order an status mail which will be assigned to the passed order
     * and will be displayed in the email panel on the right side of the batch window.
     * If the parameter "autoSend" is set to true (configurable over the checkbox in the form panel) each
     * created status mail will be send directly.
     */
    public function batchProcessAction()
    {
        $autoSend = $this->Request()->getParam('autoSend', false);
        $orders = $this->Request()->getParam('orders', array(0 => $this->Request()->getParams()));
        $documentType = $this->Request()->getParam('docType', null);
        $documentMode = $this->Request()->getParam('mode', 0);

        /** @var $namespace Enlight_Components_Snippet_Namespace */
        $namespace = Shopware()->Snippets()->getNamespace('backend/order');

        if (empty($orders)) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => $namespace->get('no_order_id_passed', 'No valid order id passed.'))
            );
            return;
        }

        foreach ($orders as $key => $data) {
            $orders[$key]['mail'] = null;
            $orders[$key]['languageSubShop'] = null;

            if (empty($data) || empty($data['id'])) {
                continue;
            }

            /**@var $order \Shopware\Models\Order\Order*/
            $order = Shopware()->Models()->find('Shopware\Models\Order\Order', $data['id']);
            if (!$order) {
                continue;
            }

            //we have to flush the status changes directly, because the "createStatusMail" function in the
            //sOrder.php core class, use the order data from the database. So we have to save the new status before we
            //create the status mail
            $statusBefore = $order->getOrderStatus();
            $clearedBefore = $order->getPaymentStatus();

            //refresh the status models to return the new status data which will be displayed in the batch list
            if (!empty($data['status']) || $data['status'] === 0) {
                $order->setOrderStatus(Shopware()->Models()->find('Shopware\Models\Order\Status', $data['status']));
            }
            if (!empty($data['cleared'])) {
                $order->setPaymentStatus(Shopware()->Models()->find('Shopware\Models\Order\Status', $data['cleared']));
            }

            try {
                Shopware()->Models()->flush($order);
            } catch (Exception $e) {
                continue;
            }

            // the setOrder function of the Shopware_Components_Document change the currency of the shop.
            // this would create a new Shop if we execute an flush();
            $this->createOrderDocuments($documentType, $documentMode, $order);


            //convert to array data to return the data to the view

            $data['paymentStatus'] = Shopware()->Models()->toArray($order->getPaymentStatus());
            $data['orderStatus'] = Shopware()->Models()->toArray($order->getOrderStatus());

            $data['mail'] = $this->checkOrderStatus($order, $statusBefore, $clearedBefore, $autoSend);
            //return the modified data array.
            $orders[$key] = $data;
        }

        $this->View()->assign(array(
            'success' => true,
            'data' => $orders
        ));
    }

    /**
     * This function is called by the batch controller after all documents were created
     * It will read the created documents' hashes from database and merge them
     */
    public function mergeDocumentsAction()
    {
        $data = $this->Request()->getParam('data', null);

        if ($data === null) {
            $this->View()->assign(array(
                'success' => false,
                'message' => 'No valid data passed.')
            );
            return;
        }

        $data = json_decode($data);

        if ($data->orders === null || count($data->orders) === 0) {
            $this->View()->assign(array(
                'success' => false,
                'message' => 'No valid order id passed.')
            );
            return;
        }

        $files = array();
        $query = $this->getOrderDocumentsQuery($data->orders, $data->docType);
        $models = $query->getResult();
        foreach ($models as $model) {
            foreach ($model->getDocuments() as $document) {
                $files[] = Shopware()->DocPath('files/documents') . $document->getHash() . ".pdf";
            }
        }
        $this->mergeDocuments($files);
    }

    /**
     * Simple helper function which actually merges a given array of document-paths
     * @param $paths
     * @return string The created document's url
     */
    private function mergeDocuments($paths)
    {
        include_once 'engine/Library/Fpdf/fpdf.php';
        include_once 'engine/Library/Fpdf/fpdi.php';

        $pdf = new FPDI();

        foreach ($paths as $path) {
            $numPages = $pdf->setSourceFile($path);
            for ($i=1;$i<=$numPages;$i++) {
                $template = $pdf->ImportPage($i);
                $size = $pdf->getTemplatesize($template);
                $pdf->AddPage('P', array($size['w'], $size['h']));
                $pdf->useTemplate($template);
            }
        }

        $hash = md5(uniqid(rand()));
        $pdf->Output($hash.'.pdf', "D");
    }

    /**
     * Internal helper function which checks if the batch process needs a document creation.
     * @param $documentType
     * @param $documentMode
     * @param \Shopware\Models\Order\Order $order
     */
    private function createOrderDocuments($documentType, $documentMode, $order)
    {
        if (!empty($documentType)) {
            $documents = $order->getDocuments();

            //create only not existing documents
            if ($documentMode == 1) {
                $alreadyCreated = false;
                foreach ($documents as $document) {
                    if ($document->getTypeId() == $documentType) {
                        $alreadyCreated = true;
                        break;
                    }
                }
                if ($alreadyCreated === false) {
                    $this->createDocument($order->getId(), $documentType);
                }
            } else {
                $this->createDocument($order->getId(), $documentType);
            }
        }
    }

    /**
     * Internal helper function to check if the order or payment status has been changed. If one
     * of the status changed, the function will create a status mail. If the passed autoSend parameter
     * is true, the created status mail will be send directly.
     * @param Order   $order
     * @param \Shopware\Models\Order\Status $statusBefore
     * @param \Shopware\Models\Order\Status $clearedBefore
     * @param boolean $autoSend
     * @return array
     */
    private function checkOrderStatus($order, $statusBefore, $clearedBefore, $autoSend)
    {
        if ($order->getOrderStatus()->getId() !== $statusBefore->getId() || $order->getPaymentStatus()->getId() !== $clearedBefore->getId()) {
            //status or cleared changed?
            if ($order->getOrderStatus()->getId() !== $statusBefore->getId()) {
                $mail = $this->getMailForOrder($order->getId(), $order->getOrderStatus()->getId());
            } else {
                $mail = $this->getMailForOrder($order->getId(), $order->getPaymentStatus()->getId());
            }

            //mail object created and auto send activated, then send mail directly.
            if (is_object($mail['mail']) && $autoSend === "true") {
                $result = Shopware()->Modules()->Order()->sendStatusMail($mail['mail']);

                //check if send mail was successfully.
                $mail['data']['sent'] = is_object($result);
            }
            return $mail['data'];
        } else {
            return null;
        }
    }

    /**
     * The sendMailAction fired from the batch window in the order backend module when the user want to send the order
     * status mail manually.
     *
     * @return array
     */
    public function sendMailAction()
    {
        $data = $this->Request()->getParams();

        /** @var $namespace Enlight_Components_Snippet_Namespace */
        $namespace = Shopware()->Snippets()->getNamespace('backend/order');

        if (empty($data)) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $data,
                'message' => $namespace->get('no_data_passed', 'No mail data passed'))
            );
            return;
        }

        try {
            $mail = clone Shopware()->Mail();
            $mail->clearRecipients();
            $mail->setSubject($this->Request()->getParam('subject', ''));
            $mail->setBodyText($this->Request()->getParam('content', ''));
            $mail->setFrom($this->Request()->getParam('fromMail', ''), $this->Request()->getParam('fromName', ''));
            $mail->addTo($this->Request()->getParam('to', ''));

            Shopware()->Modules()->Order()->sendStatusMail($mail);

            $this->View()->assign(array(
                'success' => true,
                'data' => $data
            ));
            return;
        } catch (Exception $e) {
            $this->View()->assign(array(
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $data
            ));
            return;
        }
    }

    /**
     * CRUD function of the document store. The function creates the order document with the passed
     * request parameters.
     */
    public function createDocumentAction()
    {
        try {
            $orderId =  $this->Request()->getParam('orderId', null);
            $documentType = $this->Request()->getParam('documentType', null);

            if (!empty($orderId) && !empty($documentType)) {
                $this->createDocument($orderId, $documentType);
            }

            $query = $this->getRepository()->getOrdersQuery(array(array('property' => 'orders.id', 'value' => $orderId)), null, 0, 1);
            $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
            $paginator = $this->getModelManager()->createPaginator($query);
            $order = $paginator->getIterator()->getArrayCopy();

            $this->View()->assign(array(
               'success' => true,
               'data'    => $order
            ));
        } catch (Exception $e) {
            $this->View()->assign(array(
               'success' => false,
               'data' => $this->Request()->getParams(),
               'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Internal helper function which is used from the batch function and the createDocumentAction.
     * The batch function fired from the batch window to create multiple documents for many orders.
     * The createDocumentAction fired from the detail page when the user clicks the "create Document button"
     * @param $orderId
     * @param $documentType
     * @return bool
     */
    private function createDocument($orderId, $documentType)
    {
        $renderer = "pdf"; // html / pdf


        $deliveryDate = $this->Request()->getParam('deliveryDate', null);
        if (!empty($deliveryDate)) {
            $deliveryDate = new \DateTime($deliveryDate);
            $deliveryDate = $deliveryDate->format('d.m.Y');
        }


        $displayDate = $this->Request()->getParam('displayDate', null);
        if (!empty($displayDate)) {
            $displayDate = new \DateTime($displayDate);
            $displayDate = $displayDate->format('d.m.Y');
        }

        $document = Shopware_Components_Document::initDocument(
            $orderId,
            $documentType,
            array(
                'netto'                   => (bool) $this->Request()->getParam('taxFree', false),
                'bid'                     => $this->Request()->getParam('invoiceNumber', null),
                'voucher'                 => $this->Request()->getParam('voucher', null),
                'date'                    => $displayDate,
                'delivery_date'           => $deliveryDate,
                // Don't show shipping costs on delivery note #SW-4303
                'shippingCostsAsPosition' => (int) $documentType !== 2,
                '_renderer'               => $renderer,
                '_preview'                => $this->Request()->getParam('preview', false),
                '_previewForcePagebreak'  => $this->Request()->getParam('pageBreak', null),
                '_previewSample'          => $this->Request()->getParam('sampleData', null),
                '_compatibilityMode'      => $this->Request()->getParam('compatibilityMode', null),
                'docComment'              => $this->Request()->getParam('docComment', null),
                'forceTaxCheck'           => $this->Request()->getParam('forceTaxCheck', false)
            )
        );
        $document->render();

        if ($renderer == "html") {
            exit;
        } // Debu//g-Mode

        return true;
    }

    /**
     * Fires when the user want to open a generated order document from the backend order module.
     * @return Returns the created pdf file with an echo.
     */
    public function openPdfAction()
    {
        try {
            $name = basename($this->Request()->getParam('id', null)) . '.pdf';
            $file = Shopware()->DocPath('files/documents') . $name;
            if (!file_exists($file)) {
                $this->View()->assign(array(
                    'success' => false,
                    'data' => $this->Request()->getParams(),
                    'message' => 'File not exist'
                ));
            }
            $orderModel = Shopware()->Models()->getRepository('Shopware\Models\Order\Document\Document')->findBy(array("hash"=>$this->Request()->getParam('id')));
            $orderModel = Shopware()->Models()->toArray($orderModel);
            $orderId = $orderModel[0]["documentId"];

            $response = $this->Response();
            $response->setHeader('Cache-Control', 'public');
            $response->setHeader('Content-Description', 'File Transfer');
            $response->setHeader('Content-disposition', 'attachment; filename='.$orderId.".pdf");
            $response->setHeader('Content-Type', 'application/pdf');
            $response->setHeader('Content-Transfer-Encoding', 'binary');
            $response->setHeader('Content-Length', filesize($file));
            echo readfile($file);
        } catch (Exception $e) {
            $this->View()->assign(array(
               'success' => false,
               'data' => $this->Request()->getParams(),
               'message' => $e->getMessage()
            ));
            return;
        }

        //removes the global PostDispatch Event to prevent assignments to the view that destroyed the pdf
        Enlight_Application::Instance()->Events()->removeListener(new Enlight_Event_EventHandler('Enlight_Controller_Action_PostDispatch', ''));
    }

    /**
     * Internal helper function which insert the order detail association data into the passed data array
     * @param array $data
     * @return array
     */
    private function getPositionAssociatedData($data)
    {
        //checks if the status id for the position is passed and search for the assigned status model
        if ($data['statusId'] >= 0) {
            $data['status'] = Shopware()->Models()->find('Shopware\Models\Order\DetailStatus', $data['statusId']);
        } else {
            unset($data['status']);
        }

        //checks if the tax id for the position is passed and search for the assigned tax model
        if (!empty($data['taxId'])) {
            $tax = Shopware()->Models()->find('Shopware\Models\Tax\Tax', $data['taxId']);
            if ($tax instanceof \Shopware\Models\Tax\Tax) {
                $data['tax'] = $tax;
                $data['taxRate'] = $tax->getTax();
            }
        } else {
            unset($data['tax']);
        }

        $articleDetails = null;
        // Add articleId if it's not provided by the client
        if ($data['articleId'] == 0 && !empty($data['articleNumber'])) {
            $detailRepo = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail');
            /** @var \Shopware\Models\Article\Detail $articleDetails */
            $articleDetails = $detailRepo->findOneBy(array('number' => $data['articleNumber']));
            if ($articleDetails) {
                $data['articleId'] = $articleDetails->getArticle()->getId();
            }
        }
        if (!$articleDetails && $data['articleId']) {
            /** @var \Shopware\Models\Article\Detail $articleDetails */
            $articleDetails = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail')
                ->findOneBy(array('number' => $data['articleNumber']));
        }

        //Load ean, unit and pack unit (translate if needed)
        if ($articleDetails) {
            $data['ean'] = $articleDetails->getEan() ? : $articleDetails->getArticle()->getMainDetail()->getEan();
            $unit = $articleDetails->getUnit() ? : $articleDetails->getArticle()->getMainDetail()->getUnit();
            $data['unit'] = $unit ? $unit->getName() : null;
            $data['packunit'] = $articleDetails->getPackUnit() ? : $articleDetails->getArticle()->getMainDetail()->getPackUnit();

            $languageData = Shopware()->Db()->fetchRow(
                'SELECT s_core_shops.default, s_order.language AS languageId
                FROM s_core_shops
                INNER JOIN s_order ON s_order.language = s_core_shops.id
                WHERE s_order.id = :orderId
                LIMIT 1',
                array(
                    'orderId' => $data['orderId']
                )
            );

            if (!$languageData['default']) {
                $translator = new Shopware_Components_Translation();

                // Translate unit
                if ($unit) {
                    $unitTranslation = $translator->read(
                        $languageData['languageId'],
                        'config_units',
                        1
                    );
                    if (!empty($unitTranslation[$unit->getId()]['description'])) {
                        $data['unit'] = $unitTranslation[$unit->getId()]['description'];
                    } elseif ($unit) {
                        $data['unit'] = $unit->getName();
                    }
                }

                $articleTranslation = array();

                // Load variant translations if we are adding a variant to the order
                if ($articleDetails->getId() != $articleDetails->getArticle()->getMainDetail()->getId()) {
                    $articleTranslation = $translator->read(
                        $languageData['languageId'],
                        'variant',
                        $articleDetails->getId()
                    );
                }

                // Load article translations if we are adding a main article or the variant translation is incomplete
                if (
                    $articleDetails->getId() == $articleDetails->getArticle()->getMainDetail()->getId()
                    || empty($articleTranslation['packUnit'])
                ) {
                    $articleTranslation = $translator->read(
                        $languageData['languageId'],
                        'article',
                        $articleDetails->getArticle()->getId()
                    );
                }

                if (!empty($articleTranslation['packUnit'])) {
                    $data['packUnit'] = $articleTranslation['packUnit'];
                }
            }
        }

        return $data;
    }

    /**
     * Internal helper function which insert the order association data into the passed data array.
     *
     * @param $data
     * @param $order
     * @param $billing
     * @param $shipping
     * @return array
     */
    private function getAssociatedData($data, $order, $billing, $shipping)
    {
        //check if a customer id has passed and fill the customer element with the associated customer model
        if (!empty($data['customerId'])) {
            $data['customer'] = Shopware()->Models()->find('Shopware\Models\Customer\Customer', $data['customerId']);
        } else {
            //if no customer id passed, we have to unset the array element, otherwise the existing customer model would be overwritten
            unset($data['customer']);
        }

        //if a payment id passed, load the associated payment model
        if (!empty($data['paymentId'])) {
            $data['payment'] = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $data['paymentId']);
        } else {
            unset($data['payment']);
        }

        //if a dispatch id is passed, load the associated dispatch model
        if (!empty($data['dispatchId'])) {
            $data['dispatch'] = Shopware()->Models()->find('Shopware\Models\Dispatch\Dispatch', $data['dispatchId']);
        } else {
            unset($data['dispatch']);
        }

        //if a shop id is passed, load the associated shop model
        if (!empty($data['shopId'])) {
            $data['shop'] = Shopware()->Models()->find('Shopware\Models\Shop\Shop', $data['shopId']);
        } else {
            unset($data['shop']);
        }

        //if a status id is passed, load the associated order status model
        if (isset($data['status']) && $data['status'] !== null) {
            $data['orderStatus'] = Shopware()->Models()->find('Shopware\Models\Order\Status', $data['status']);
        } else {
            unset($data['orderStatus']);
        }

        //if a payment status id is passed, load the associated payment status model
        if (isset($data['cleared']) && $data['cleared'] !== null) {
            $data['paymentStatus'] = Shopware()->Models()->find('Shopware\Models\Order\Status', $data['cleared']);
        } else {
            unset($data['paymentStatus']);
        }

        //the documents will be created over the "createDocumentAction" so we have to unset the array element, otherwise the
        //created documents models would be overwritten.
        unset($data['documents']);

        //For now the paymentInstances information is not editable, so it's just discarded at this point
        unset($data['paymentInstances']);

        $data['billing'] = $this->prepareAddressData($data['billing'][0]);
        $data['shipping'] = $this->prepareAddressData($data['shipping'][0]);
        $data['attribute'] = $data['attribute'][0];
        $data['billing']['attribute'] = $data['billingAttribute'][0];
        $data['shipping']['attribute'] = $data['shippingAttribute'][0];

        //at least we return the prepared associated data.
        return $data;
    }

    /**
     * Prepare address data - loads countryModel from a given countryId
     *
     * @param $data Array
     * @return Array
     */
    protected function prepareAddressData(array $data)
    {
        if (isset($data['countryId']) && !empty($data['countryId'])) {
            $countryModel = $this->getCountryRepository()->find($data['countryId']);
            if ($countryModel) {
                $data['country'] = $countryModel;
            }
            unset($data['countryId']);
        }

        if (isset($data['stateId']) && !empty($data['stateId'])) {
            $stateModel = Shopware()->Models()->find('Shopware\Models\Country\State', $data['stateId']);
            if ($stateModel) {
                $data['state'] = $stateModel;
            }
            unset($data['stateId']);
        }

        return $data;
    }

    /**
     * Creates the status mail order for the passed order id and new status object.
     *
     * @param $orderId
     * @param $statusId
     * @internal param \Shopware\Models\Order\Order $order
     * @return array
     */
    private function getMailForOrder($orderId, $statusId)
    {
        try {
            /**@var $mail Enlight_Components_Mail */
            $mail = Shopware()->Modules()->Order()->createStatusMail($orderId, $statusId);

            if ($mail instanceof Enlight_Components_Mail) {
                return array(
                    'mail' => $mail,
                    'data' => array(
                        'error' => false,
                        'content' => $mail->getPlainBodyText(),
                        'subject' => $mail->getPlainSubject(),
                        'to' => implode(', ', $mail->getTo()),
                        'fromMail' => $mail->getFrom(),
                        'fromName' => $mail->getFromName(),
                        'sent' => false,
                        'orderId' => $orderId
                    )
                );
            } else {
                return array();
            }
        } catch (Exception $e) {
            return array(
                'mail' => null,
                'data' => array(
                    'error' => true,
                    'message' => $e->getMessage()
                )
            );
        }
    }
}
