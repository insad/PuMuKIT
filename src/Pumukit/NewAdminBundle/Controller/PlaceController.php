<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\NewAdminBundle\Form\Type\TagType;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Security("is_granted('ROLE_ACCESS_TAGS')")
 * @Route("/places")
 */
class PlaceController extends Controller implements NewAdminController
{
    /**
     * @param Request $request
     *
     * @return array
     *
     * @Route("/", name="pumukitnewadmin_places_index")
     * @Template("PumukitNewAdminBundle:Place:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $placeTag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => 'PLACES'));
        $places = $dm->getRepository('PumukitSchemaBundle:Tag')->findBy(array('parent.$id' => new \MongoId($placeTag->getId())), array("title.".$request->getLocale() => 1));

        return array('places' => $places);
    }

    /**
     * @param Tag $tag
     *
     * @return array
     *
     * @Route("/children/{id}", name="pumukitnewadmin_places_children")
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:Place:children_list.html.twig")
     */
    public function childrenAction(Tag $tag)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $tag = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('_id' => $tag->getId()));
        $children = $tag->getChildren();

        return array('children' => $children);
    }

    /**
     * @param Tag $tag
     *
     * @return array
     *
     * @Route("/preview/{id}", name="pumukitnewadmin_places_children_preview")
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"id": "id"}})
     * @Template("PumukitNewAdminBundle:Place:preview_data.html.twig")
     */
    public function previewAction(Tag $tag)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();

        $multimediaObjects = $dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy(array('tags._id' =>  new \MongoId($tag->getId())));

        $series = array();
        foreach ($multimediaObjects as $multimediaObject) {
            $series[$multimediaObject->getSeries()->getId()] = $multimediaObject->getSeries()->getTitle();
        }

        return array('tag' => $tag, 'series' => $series);
    }

    /**
     * @param Request $request
     * @param null    $id
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/create/{id}", name="pumukitnewadmin_places_create")
     * @Template("PumukitNewAdminBundle:Place:create.html.twig")
     */
    public function createAction(Request $request, $id = null)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $translator = $this->get('translator');

        if($id) {
            $parent = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('_id' => new \MongoId($id)));
        } else {
            $parent = $dm->getRepository('PumukitSchemaBundle:Tag')->findOneBy(array('cod' => 'PLACES'));
        }

        $suggested_code = $this->autogenerateCode($parent, $id);

        $tag = new Tag();
        $tag->setCod($suggested_code);
        $tag->setParent($parent);

        $form = $this->createForm(new TagType($translator, $request->getLocale()), $tag);

        if ($form->isValid()) {
            try {
                $dm->persist($tag);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()), JsonResponse::HTTP_CONFLICT);
            }

            return $this->redirectToRoute('pumukitnewadmin_places_index');
        }

        return array('tag' => $tag, 'form' => $form->createView(), 'suggested_code' => $suggested_code);
    }

    /**
     * @param $parent
     * @param $id
     *
     * @return int
     */
    private function autogenerateCode($parent, $id)
    {
        $code = array();
        $delimiter = 'PLACE';
        if($id) {
            $delimiter = 'PRECINCT';
        }

        foreach($parent->getChildren() as $child) {
            $tagCode = explode($delimiter, $child->getCod());
            $code[] = $tagCode[1];
        }

        $value = (int) array_pop($code);
        $suggested_code = $value + 1;

        if($id) {
            $rootCode = $parent->getCod();
            $suggested_code = $rootCode . $delimiter . $suggested_code;
        } else {
            $suggested_code = $delimiter . $suggested_code;
        }

        return $suggested_code;
    }

}
