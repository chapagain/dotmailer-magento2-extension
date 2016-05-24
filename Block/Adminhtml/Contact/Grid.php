<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Contact;


class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    protected $_gridFactory;
    protected $_storeFactory;
    protected $_importerFactory;


    /**
     * Grid constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Resource\Contact\CollectionFactory       $collectionFactory
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory $importerFactory
     * @param \Magento\Backend\Block\Template\Context                               $context
     * @param \Magento\Backend\Helper\Data                                          $backendHelper
     * @param \Magento\Store\Model\System\StoreFactory                              $storeFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory                           $gridFactory
     * @param \Magento\Framework\Module\Manager                                     $moduleManager
     * @param array                                                                 $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Resource\Contact\CollectionFactory $collectionFactory,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory $importerFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Store\Model\System\StoreFactory $storeFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $gridFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_contactFactory    = $gridFactory;
        $this->_importerFactory   = $importerFactory;
        $this->moduleManager      = $moduleManager;
        $this->_storeFactory      = $storeFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('contact');
        $this->setDefaultSort('email_contact_id');
        $this->setDefaultDir('DESC');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $this->setCollection($this->_collectionFactory->create());

        return parent::_prepareCollection();
    }

    /**
     *
     * @return $this
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'email_contact_id',
            [
                'header'           => __('Contact ID'),
                'type'             => 'number',
                'index'            => 'email_contact_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        )->addColumn(
            'email',
            [
                'header' => __('Email'),
                'type'   => 'text',
                'index'  => 'email',
                'class'  => 'xxx'
            ]
        )->addColumn(
            'customer_id',
            [
                'header' => __('Customer ID'),
                'type'   => 'number',
                'index'  => 'customer_id'
            ]
        )->addColumn(
            'is_guest',
            [
                'header'  => __('Is Guest'),
                'type'    => 'options',
                'index'   => 'is_guest',
                'options' => ['1' => 'Guest', '' => 'Not Guest']
            ]
        )->addColumn(
            'is_subscriber',
            [
                'header'  => __('Is Subscriber'),
                'index'   => 'is_subscriber',
                'type'    => 'options',
                'options' => ['0' => 'Not Subscriber', '1' => 'Subscriber'],
                'escape'  => true
            ]
        )->addColumn('subscriber_status', [
            'header'  => 'Subscriber Status',
            'align'   => 'center',
            'index'   => 'subscriber_status',
            'type'    => 'options',
            'options' => [
                '1' => 'Subscribed',
                '2' => 'Not Active',
                '3' => 'Unsubscribed',
                '4' => 'Unconfirmed'
            ],
            'escape'  => true,
        ])->addColumn('email_imported', [
            'header'   => __('Email Imported'),
            'align'    => 'center',
            'index'    => 'email_imported',
            'escape'   => true,
            'type'     => 'options',
            'options'  => $this->_importerFactory->create()->getOptions(),
            'renderer' => 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
            //'filter_condition_callback' => array($this, 'filterCallbackContact'
        ])->addColumn('subscriber_imported', array(
            'header'   => __('Subscriber Imported'),
            'sortable' => false,
            'align'    => 'center',
            'index'    => 'subscriber_imported',
            'escape'   => true,
            'type'     => 'options',
            'renderer' => 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
            'options'  => $this->_importerFactory->create()->getOptions(),
            //'filter_condition_callback' => [$this, '_filterCallbackContact']
        ))->addColumn('suppressed', array(
            'header'                    => __('Suppressed'),
            'align'                     => 'right',
            'index'                     => 'suppressed',
            'escape'                    => true,
            'type'                      => 'options',
            'options'                   => [
                '1'    => 'Suppressed',
                'null' => 'Not Suppressed'
            ],
            'filter_condition_callback' => array(
                $this,
                '_filterCallbackContact'
            )
        ))->addColumn('website_id', array(
            'header'  => __('Website'),
            'align'   => 'center',
            'type'    => 'options',
            'options' => $this->_storeFactory->create()
                ->getWebsiteOptionHash(true),
            'index'   => 'website_id',
        ));


        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        return parent::_prepareColumns();
    }


    /**
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('id');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label'   => __('Delete'),
                'url'     => $this->getUrl('*/*/massDelete'),
                'confirm' => __('Are you sure?')
            ]
        );

        return $this;
    }

    /**
     * Filter subscribers/contacts.
     *
     * @param $collection
     * @param $column
     */
    public function _filterCallbackContact(
        $collection,
        \Magento\Framework\DataObject $column
    ) {
        $field = $column->getFilterIndex() ? $column->getFilterIndex()
            : $column->getIndex();
        $value = $column->getFilter()->getValue();

        if ($value == 'null') {
            $collection->addFieldToFilter($field, array('null' => true));
        } else {
            $collection->addFieldToFilter($field, array('notnull' => true));
        }
    }
}
