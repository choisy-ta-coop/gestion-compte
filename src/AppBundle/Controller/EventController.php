<?php

namespace AppBundle\Controller;
use AppBundle\Entity\Beneficiary;
use AppBundle\Entity\Event;
use AppBundle\Entity\Proxy;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Session\Session;


/**
 * Event controller.
 *
 * @Route("event")
 */
class EventController extends Controller
{

    /**
     * Lists all events.
     *
     * @Route("/", name="event_list")
     * @Method("GET")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listAction(Request $request){

        $em = $this->getDoctrine()->getManager();
        $events = $em->getRepository('AppBundle:Event')->findAll();
        return $this->render('admin/event/list.html.twig', array(
            'events' => $events,
        ));
    }

    /**
     * Lists all proxy
     *
     * @Route("/proxies_list", name="proxies_list")
     * @Method("GET")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listProxiesAction(){
        $em = $this->getDoctrine()->getManager();
        $proxies = $em->getRepository('AppBundle:Proxy')->findAll();
        $delete_forms = array();
        foreach ($proxies as $proxy){
            $delete_forms[$proxy->getId()] = $this->getProxyDeleteForm($proxy)->createView();
        }
        return $this->render('admin/event/proxy/list.html.twig', array(
            'proxies' => $proxies,
            'delete_forms' => $delete_forms,
            'event' => null,
        ));
    }

    /**
     * Lists all proxy for one event.
     *
     * @Route("/{id}/proxies_list", name="event_proxies_list")
     * @Method("GET")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listEventProxiesAction(Event $event,Request $request){

        $proxies = $event->getProxies();
        $delete_forms = array();
        foreach ($proxies as $proxy){
            $delete_forms[$proxy->getId()] = $this->getProxyDeleteForm($proxy)->createView();
        }
        return $this->render('admin/event/proxy/list.html.twig', array(
            'proxies' => $proxies,
            'delete_forms' => $delete_forms,
            'event' => $event,
        ));
    }

    /**
     * Comission new
     *
     * @Route("/new", name="event_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function newAction(Request $request)
    {

        $session = new Session();

        $event = new Event();
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm('AppBundle\Form\EventType', $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($event);
            $em->flush();

            $session->getFlashBag()->add('success', 'L\'événement a bien été créé !');

            return $this->redirectToRoute('event_edit', array('id' => $event->getId()));

        }

        return $this->render('admin/event/new.html.twig', array(
            'commission' => $event,
            'form' => $form->createView(),
            'errors' => $form->getErrors()
        ));
    }

    /**
     * Comission edit
     *
     * @Route("/{id}/edit", name="event_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function editAction(Request $request,Event $event)
    {
        $session = new Session();

        $form = $this->createForm('AppBundle\Form\EventType', $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();

            $em->persist($event);
            $em->flush();

            $session->getFlashBag()->add('success', 'L\'événement a bien été édité !');

            return $this->redirectToRoute('event_list');

        }

        return $this->render('admin/event/edit.html.twig', array(
            'form' => $form->createView(),
            'delete_form' => $this->getDeleteForm($event)->createView(),
        ));
    }

    /**
     * Event delete
     *
     * @Route("/{id}", name="event_delete")
     * @Method({"DELETE"})
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function removeAction(Request $request,Event $event)
    {
        $session = new Session();
        $form = $this->getDeleteForm($event);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->remove($event);
            $em->flush();
            $session->getFlashBag()->add('success', 'L événement a bien été supprimée !');
        }
        return $this->redirectToRoute('event_list');
    }

    /**
     * Proxy delete
     *
     * @Route("/proxy/{id}", name="proxy_delete")
     * @Method({"DELETE"})
     * @Security("has_role('ROLE_SUPER_ADMIN')")
     */
    public function removeProxyAction(Request $request,Proxy $proxy)
    {
        $session = new Session();
        $form = $this->getProxyDeleteForm($proxy);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->remove($proxy);
            $em->flush();
            $session->getFlashBag()->add('success', 'La procuration a bien été supprimée !');
        }
        return $this->redirectToRoute('event_proxies_list',array('id'=>$proxy->getEvent()->getId()));
    }

    /**
     * @param Event $event
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getDeleteForm(Event $event){
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('event_delete', array('id' => $event->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

    /**
     * @param Proxy $proxy
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getProxyDeleteForm(Proxy $proxy){
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('proxy_delete', array('id' => $proxy->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

    /**
     * Proxy new
     *
     * @Route("/{id}/proxy/give", name="event_proxy_give")
     * @Method({"GET", "POST"})
     */
    public function newProxyAction(Event $event,Request $request,\Swift_Mailer $mailer){

        $em = $this->getDoctrine()->getManager();
        $current_app_user = $this->get('security.token_storage')->getToken()->getUser();
        $myproxy = $em->getRepository('AppBundle:Proxy')->findOneBy(array("event"=>$event,"giver"=>$current_app_user));
        $session = new Session();
        if ($myproxy){
            $session->getFlashBag()->add('error', 'Oups, tu as déjà donné une procuration');
            return $this->redirectToRoute('homepage');
        }
        if ($current_app_user->getLastRegistration()->getDate() < $event->getMinDateOfLastRegistration()){
            $session->getFlashBag()->add('error', 'Oups, seuls les membres qui ont adhéré ou ré-adhéré après le '.
                $event->getMinDateOfLastRegistration()->format('d M Y').
                ' peuvent voter à cet événement. Penses à mettre à jour ton adhésion pour participer !');
            return $this->redirectToRoute('homepage');
        }

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('event_proxy_give', array('id' => $event->getId())))
            ->setMethod('POST')
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $proxy = $em->getRepository('AppBundle:Proxy')->findOneBy(array("event"=>$event,"giver"=>null));
            if (!$proxy){
                $proxy = new Proxy();
                $proxy->setEvent($event);
                $proxy->setCreatedAt(new \DateTime());
            }

            $proxy->setGiver($current_app_user);
            $em->persist($proxy);
            $em->flush();
            $session = new Session();
            $session->getFlashBag()->add('success', 'Votre réquète a bien été acceptée !');

            if ($proxy->getGiver() && $proxy->getOwner()){
                $this->sendProxyMail($proxy,$mailer);
            }

            return $this->redirectToRoute('homepage');
        }

        if ($request->get("beneficiary") > 0){
            $em = $this->getDoctrine()->getManager();
            $beneficiary = $em->getRepository('AppBundle:Beneficiary')->find($request->get("beneficiary"));
            if ($beneficiary && $beneficiary->getUser()->getMemberNumber() == $request->get("member_number")){

                $proxy = $em->getRepository('AppBundle:Proxy')->findOneBy(array("event"=>$event,"giver"=>null,"owner"=>$beneficiary));
                if (!$proxy){
                    $proxy = new Proxy();
                    $proxy->setEvent($event);
                    $proxy->setCreatedAt(new \DateTime());
                    $proxy->setOwner($beneficiary);
                }
                $current_app_user = $this->get('security.token_storage')->getToken()->getUser();
                $proxy->setGiver($current_app_user);
                $confirm_form = $this->createForm('AppBundle\Form\ProxyType', $proxy);
                $confirm_form->handleRequest($request);

                if ($confirm_form->isSubmitted() && $confirm_form->isValid()) {
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($proxy);
                    $em->flush();
                    $session = new Session();
                    $session->getFlashBag()->add('success', 'Votre réquète a bien été acceptée !');

                    if ($proxy->getGiver() && $proxy->getOwner()){
                        $this->sendProxyMail($proxy,$mailer);
                    }

                    return $this->redirectToRoute('homepage');
                }

                return $this->render('default/event/proxy/give.html.twig', array(
                    'event' => $event,
                    'form' => $form->createView(),
                    'confirm_form' => $confirm_form->createView(),
                ));

            }else{
                return $this->redirectToRoute('homepage');
            }
        }

        $search_form = $this->createFormBuilder()
            ->setAction($this->generateUrl('event_proxy_find_beneficiary', array('id' => $event->getId())))
            ->add('firstname', TextType::class, array('label' => 'le prénom'))
            ->setMethod('POST')
            ->getForm();

        return $this->render('default/event/proxy/give.html.twig', array(
            'event' => $event,
            'form' => $form->createView(),
            'search_form' => $search_form->createView()
        ));
    }

    /**
     * @Route("/{id}/proxy/find_beneficiary", name="event_proxy_find_beneficiary")
     * @Method({"POST"})
     */
    public function findBeneficiaryAction(Event $event,Request $request){
        $search_form = $this->createFormBuilder()
            ->setAction($this->generateUrl('event_proxy_find_beneficiary', array('id' => $event->getId())))
            ->add('firstname', TextType::class, array('label' => 'le prénom'))
            ->setMethod('POST')
            ->getForm();

        $session = new Session();

        if ($search_form->handleRequest($request)->isValid()) {
            $firstname = $search_form->get('firstname')->getData();
            $em = $this->getDoctrine()->getManager();
            $qb = $em->createQueryBuilder();
            $beneficiaries = $qb->select('b')->from('AppBundle\Entity\Beneficiary', 'b')
                ->join('b.user', 'u')
                ->where( $qb->expr()->like('b.firstname', $qb->expr()->literal('%'.$firstname.'%')))
                ->andWhere("u.withdrawn != 1 or u.withdrawn is NULL" )
                ->orderBy("u.member_number", 'ASC')
                ->getQuery()
                ->getResult();
            return $this->render('user/find_user_number.html.twig', array(
                'form' => null,
                'beneficiaries' => $beneficiaries,
                'return_path' => 'event_proxy_give',
                'params' => array('id'=>$event->getId())
            ));
        }
        $session->getFlashBag()->add('error',"oups, quelque chose c'est mal passé");
        return $this->redirectToRoute("event_proxy_give",array('id'=>$event->getId()));
    }

    /**
     * Proxy take
     *
     * @Route("/{id}/proxy/take", name="event_proxy_take")
     * @Method({"GET","POST"})
     */
    public function acceptProxyAction(Event $event,Request $request,\Swift_Mailer $mailer){

        $em = $this->getDoctrine()->getManager();
        $current_app_user = $this->get('security.token_storage')->getToken()->getUser();
        $myproxy = $em->getRepository('AppBundle:Proxy')->findOneBy(array("event"=>$event,"giver"=>$current_app_user));
        $session = new Session();
        if ($myproxy){
            $session->getFlashBag()->add('error', 'Oups, tu as déjà donné une procuration');
            return $this->redirectToRoute('homepage');
        }
        if ($current_app_user->getLastRegistration()->getDate() < $event->getMinDateOfLastRegistration()){
            $session->getFlashBag()->add('error', 'Oups, seuls les membres qui ont adhéré ou ré-adhéré après le '.
                $event->getMinDateOfLastRegistration()->format('d M Y').
                ' peuvent voter à cet événement. Penses à mettre à jour ton adhésion pour participer !');
            return $this->redirectToRoute('homepage');
        }
        $proxy = $em->getRepository('AppBundle:Proxy')->findOneBy(array("event"=>$event,"owner"=>null));
        if (!$proxy){
            $proxy = new Proxy();
            $proxy->setEvent($event);
            $proxy->setCreatedAt(new \DateTime());
        }
        $form = $this->createForm('AppBundle\Form\ProxyType', $proxy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $myproxy = $em->getRepository('AppBundle:Proxy')->findOneBy(array("event"=>$event,"owner"=>$form->getData()->getOwner()));
            if ($myproxy){
                $session->getFlashBag()->add('error', $myproxy->getOwner()->getFirstname().' accepte déjà une procuration.');
                return $this->redirectToRoute('event_proxy_take',array('id'=>$event->getId()));
            }
            $em->persist($proxy);
            $em->flush();
            $session->getFlashBag()->add('success', 'Votre réquète a bien été acceptée !');

            if ($proxy->getGiver() && $proxy->getOwner()){
                $this->sendProxyMail($proxy,$mailer);
            }

            return $this->redirectToRoute('homepage');

        }

        return $this->render('default/event/proxy/take.html.twig', array(
            'event' => $event,
            'form' => $form->createView()
        ));

    }

    public function sendProxyMail(Proxy $proxy,\Swift_Mailer $mailer){
        $owner = (new \Swift_Message('['.$proxy->getEvent()->getTitle().'] procuration'))
            ->setFrom('membres@lelefan.org')
            ->setTo($proxy->getOwner()->getEmail())
            ->setBody(
                $this->renderView(
                    'emails/proxy_owner.html.twig',
                    array('proxy' => $proxy)
                ),
                'text/html'
            );
        $giver = (new \Swift_Message('['.$proxy->getEvent()->getTitle().'] votre procuration'))
            ->setFrom('membres@lelefan.org')
            ->setTo($proxy->getGiver()->getEmail())
            ->setBody(
                $this->renderView(
                    'emails/proxy_giver.html.twig',
                    array('proxy' => $proxy)
                ),
                'text/html'
            );
        $mailer->send($owner);
        $mailer->send($giver);

    }
}
