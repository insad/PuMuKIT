<?php

namespace Pumukit\SchemaBundle\Controller;

use Pumukit\SchemaBundle\Document\Annotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 *  @Route("/annotation")
 */
class AnnotationsAPIController extends Controller
{
    /**
     * @Route("/annotations.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     */
    public function getAction(Request $request)
    {
        //TODO: Do the annotation getting using a service function.
        //$opencastAnnotationService = $this->container->get('video_editor.opencast_annotations');
        $serializer = $this->get('serializer');

        $episode = $request->get('episode');
        $type = $request->get('type');
        $day = $request->get('day');

        $limit = $request->get('limit') ?: 10;
        $offset = $request->get('offset') ?: 0;
        $total = 10;

        //TODO: Do the annotation getting using a service function.
        //$resAnnotations = $opencastAnnotationService->getOpencastAnnotations();
        $resAnnotations = array();
        $annonRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Annotation');
        $annonQB = $annonRepo->createQueryBuilder();

        if ($episode) {
            $annonQB->field('multimediaObject')->equals(new \MongoId($episode));
        }

        if ($type) {
            $annonQB->field('type')->equals($type);
        }

        if ($day) {
            $minDate = new \DateTime($day);
            $minDate->setTime(0, 0, 0);
            $maxDate = new \DateTime($day);
            $maxDate->setTime(23, 59, 59);
            $annonQB->field('created')->gte($minDate)->lte($maxDate);
        }

        $total = clone($annonQB);
        $total = $total->count()->getQuery()->execute();

        $annonQB->limit($limit)->skip($offset);
        $resAnnotations = $annonQB->getQuery()->execute()->toArray();
        $annotations = array();
        foreach( $resAnnotations as $ann) {
            $annotations[] = $ann;
        }

        $data = array('annotations' => array('limit' => $limit,
                                             'offset' => $offset,
                                             'total' => $total,
                                             'annotation' => $annotations, ));

        $response = $serializer->serialize($data, $request->getRequestFormat());

        return new Response($response);
    }

    /**
     * @Route("/{id}.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     * @Method("GET")
     */
    public function getByIdAction(Annotation $annotation, Request $request)
    {
        $serializer = $this->get('serializer');
        $data = array('annotation' => array('annotationId' => $annotation->getId(),
                                              'mediapackageId' => $annotation->getMultimediaObject(),
                                              'userId' => $annotation->getUserId(),
                                              'sessionId' => $annotation->getSession(),
                                              'inpoint' => $annotation->getInPoint(),
                                              'outpoint' => $annotation->getOutPoint(),
                                              'length' => $annotation->getLength(),
                                              'type' => $annotation->getType(),
                                              'isPrivate' => $annotation->getIsPrivate(),
                                              'value' => $annotation->getValue(),
                                              'created' => $annotation->getCreated(),
        ));
        $response = $serializer->serialize($data, $request->getRequestFormat());

        return new Response($response);
    }

    /**
     * @Route("/")
     * @Method("PUT")
     * @Security("has_role('ROLE_ACCESS_MULTIMEDIA_SERIES')")
     */
    public function createNewAction(Request $request)
    {
        //TODO: Do the annotation getting using a service function.
        //$opencastAnnotationService = $this->container->get('video_editor.opencast_annotations');
        $serializer = $this->get('serializer');

        //$annonRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Annotation');
        $episode = $request->get('episode');
        $type = $request->get('type');
        $value = $request->get('value');
        $inPoint = $request->get('in');
        $outPoint = $request->get('out') ?: 100;
        $isPrivate = $request->get('isPrivate') ?: false;

        $annotation = new Annotation();
        $annotation->setMultimediaObject(new \MongoId($episode));
        $annotation->setType($type);
        $annotation->setValue($value);
        $annotation->setInPoint($inPoint);
        $annotation->setOutPoint($outPoint);
        $annotation->setIsPrivate($isPrivate);
        $annotation->setLength(0);//This field is not very useful.
        $annotation->setCreated(new \DateTime());
        $userId = $this->getUser() ? $this->getUser()->getId() : 'anonymous';
        $annotation->setUserId($userId);//TODO: How do we get the user_id?
        $session = new Session(); //Using symfony sessions instead of php session_id()
        $session = $session->getId();
        $annotation->setSession($session);

        $this->get('doctrine_mongodb.odm.document_manager')->persist($annotation);
        $this->get('doctrine_mongodb.odm.document_manager')->flush();

        $data = array('annotation' => array('annotationId' => $annotation->getId(),
                                              'mediapackageId' => $annotation->getMultimediaObject(),
                                              'userId' => $annotation->getUserId(),
                                              'sessionId' => $annotation->getSession(),
                                              'inpoint' => $annotation->getInPoint(),
                                              'outpoint' => $annotation->getOutPoint(),
                                              'length' => $annotation->getLength(),
                                              'type' => $annotation->getType(),
                                              'isPrivate' => $annotation->getIsPrivate(),
                                              'value' => $annotation->getValue(),
                                              'created' => $annotation->getCreated(),
        ));
        $response = $serializer->serialize($data, 'json');

        return new Response($response);
    }

    /**
     * @Route("/{id}")
     * @Method("PUT")
     * @Security("has_role('ROLE_ACCESS_MULTIMEDIA_SERIES')")
     */
    public function editAction(Annotation $annotation, Request $request)
    {
        //TODO: Do the annotation getting using a service function.
        //$opencastAnnotationService = $this->container->get('video_editor.opencast_annotations');
        $serializer = $this->get('serializer');

        $value = $request->get('value');
        $annotation->setValue($value);
        $annonRepo = $this->get('doctrine_mongodb.odm.document_manager')->persist($annotation);
        $annonRepo = $this->get('doctrine_mongodb.odm.document_manager')->flush();
        $data = array('annotation' => array('annotationId' => $annotation->getId(),
                                              'mediapackageId' => $annotation->getMultimediaObject(),
                                              'userId' => $annotation->getUserId(),
                                              'sessionId' => $annotation->getSession(),
                                              'inpoint' => $annotation->getInPoint(),
                                              'outpoint' => $annotation->getOutPoint(),
                                              'length' => $annotation->getLength(),
                                              'type' => $annotation->getType(),
                                              'isPrivate' => $annotation->getIsPrivate(),
                                              'value' => $annotation->getValue(),
                                              'created' => $annotation->getCreated(),
        ));
        $response = $serializer->serialize($data, 'xml');

        return new Response($response);
    }

    /**
     * @Route("/{id}")
     * @Method("DELETE")
     * @Security("has_role('ROLE_ACCESS_MULTIMEDIA_SERIES')")
     */
    public function deleteAction(Annotation $annotation, Request $request)
    {
        $this->get('doctrine_mongodb.odm.document_manager')->remove($annotation);
        $this->get('doctrine_mongodb.odm.document_manager')->flush();

        $response = $serializer->serialize($annotation, 'xml');

        return new Response($response);
    }
}
