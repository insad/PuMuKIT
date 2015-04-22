<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class WidgetController extends Controller
{
    /**
     * @Template()
     */
    public function menuAction()
    {
      return array();
    }
    
    /**
     * @Template()
     */
    public function breadcrumbsAction()
    {
      $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
      return array('breadcrumbs' => $breadcrumbs->getBreadcrumbs());
    }

    /**
     * @Template()
     */
    public function statsAction()
    {
      $mmRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
      $seriesRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:series');

      $counts = array('series' => $seriesRepo->count(),
                      'mms' => $mmRepo->count(),
                      'hours' => bcdiv($mmRepo->countDuration(), 3600, 2));
      return array('counts' => $counts);
    }

    /**
     * @Template()
     */
    public function contactAction()
    {
      return array();
    }

}
