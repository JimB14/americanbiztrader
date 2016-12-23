<?php

namespace App\Controllers\Admin;

use \Core\View;
use \App\Models\User;
use \App\Models\Broker;
use \App\Models\Listing;
use \App\Models\BrokerAgent;
use \App\Models\Category;
use \App\Models\State;
use \App\Models\Realtylisting;
use \App\Models\Paypal;

/**
 * Admin controller
 *
 * PHP version 7.0
 */
class Brokers extends \Core\Controller
{
    /**
     * Before filter
     *
     * @return void
     */
    protected function before()
    {
        //if SESSION is not set, send to login page
        if(!isset($_SESSION['user']))
        {
            header("Location: /login");
            exit();
        }
    }


    /**
     * Show the Admin Panel index page
     *
     * @return void
     */
    public function indexAction()
    {
        // retrieve GET variable
        $user_id = (isset($_REQUEST['user_id'])) ? filter_var($_REQUEST['user_id'], FILTER_SANITIZE_STRING) : '';

        // if user not logged in send to login page
        if(!$user_id)
        {
            header("Location: /login");
            exit();
        }
        else
        {
            // get broker data from User model
            $broker = Broker::getBrokerData($user_id);

            // test
            // echo '<pre>';
            // print_r($broker);
            // echo '</pre>';
            // exit();

            // store broker ID in session varible
            $_SESSION['broker_id'] = $broker->broker_id;

            // get agents id, last name, first name & broker ID only for drop-down
            $agents = BrokerAgent::getNamesOfAllBrokerAgents($_SESSION['broker_id'], $orderby = 'broker_agents.last_name');

            // test
            // echo '<pre>';
            // print_r($agents);
            // echo '</pre>';
            // exit();

            // render view & pass $broker object
            View::renderTemplate('Admin/index.html', [
                'broker' => $broker,
                'agents' => $agents
            ]);
        }
    }




    public function myAccountAction()
    {
        // retrieve GET variable
        $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

        // get agents id, last name, first name & broker ID only for drop-down
        $agents = BrokerAgent::getNamesOfAllBrokerAgents($_SESSION['broker_id'], $orderby = 'broker_agents.last_name');

        // get company name
        $company_name = Broker::getBrokerCompanyName($broker_id);

        // get user data
        $user = User::getUser($_SESSION['user_id']);

        // get number of agents
        $agent_count = BrokerAgent::getCountofAgents($broker_id);

        // get paypal_log data
        $data = Paypal::getTransactionData($user->id);

        $last_trx = $data[0]->TRANSTIME;
        $amount = $data[0]->AMT;
        $profileid = $data[0]->PROFILEID;

        // test
        // echo $amount;
        // echo '<pre>';
        // print_r($data);
        // echo '</pre>';
        // exit();

        // render view
        View::renderTemplate('Admin/Show/my-account.html', [
            'agents'        => $agents,
            'company_name'  => $company_name,
            'user'          => $user,
            'last_trx'      => $last_trx,
            'amount'        => $amount,
            'profileid'     => $profileid,
            'agent_count'   => $agent_count
        ]);
    }




    public static function companyProfileAction()
    {
        // retrieve GET variable
        $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

        // get broker data from broker model
        $broker = Broker::getBrokerDetails($broker_id);

        // get user data
        $user = User::getUser($broker->user_id);

        // test
        // echo "<pre>";
        // print_r($broker);
        // echo "</pre>";

        // get states for drop-down
        $states = State::getStates();

        // test
        // echo '<pre>';
        // print_r($broker);
        // echo '</pre>';
        // exit();

        // get agents id, last name, first name & broker ID only for drop-down
        $agents = BrokerAgent::getNamesOfAllBrokerAgents($_SESSION['broker_id'], $orderby = 'broker_agents.last_name');

        // render view & pass $broker object
        View::renderTemplate('Admin/Show/company-profile.html', [
            'broker'  => $broker,
            'states'  => $states,
            'agents'  => $agents,
            'user'    => $user
        ]);
    }




    public static function previewCompanyPage()
    {
        // retrieve GET variable
        $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

        // get all listings for this broker
        $listings = Listing::getListings($broker_id, $limit = null);

        // test
        // echo '<pre>';
        // print_r($listings);
        // echo '</pre>';

        // get listing broker data from Broker model
        $broker = Broker::getBrokerDetails($broker_id);

        // test
        // echo '<pre>';
        // print_r($broker);
        // echo '</pre>';

        // broker status = 'sold' listings
        $broker_sold_listings = Listing::allBrokerSoldListings($broker_id);

        // test
        // echo '<pre>';
        // print_r($broker_sold_listings);
        // echo '</pre>';

        // get list of agents (team) from BrokerAgent model
        $agent_list = BrokerAgent::getAllBrokerAgents($limit=null, $broker_id, $orderby = 'broker_agents.last_name');

        // test
        // echo '<pre>';
        // print_r($agent_list);
        // echo '</pre>';

        // display in view
        View::renderTemplate('Buy/all-broker-listings.html', [
            'listings'              => $listings,
            'broker'                => $broker,
            'broker_sold_listings'  => $broker_sold_listings,
            'agent_list'            => $agent_list,
        ]);
    }




    public function addNewAgent()
    {
      // retrieve GET variable
      $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

      // get agents id, last name, first name & broker ID only for drop-down
      $agents = BrokerAgent::getNamesOfAllBrokerAgents($_SESSION['broker_id'], $orderby = 'broker_agents.last_name');

      // get states array for drop-down
      $states = State::getStates();

      // render view
      View::renderTemplate('Admin/Add/add-new-agent.html', [
          'broker_id'  => $broker_id,
          'agents'     => $agents,
          'states'     => $states
      ]);
    }




    public function postNewAgent()
    {
        // retrieve GET variable
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // retrieve first and last name for quick db check
        $first_name = ( isset($_POST['first_name']) )? filter_var($_POST['first_name'], FILTER_SANITIZE_STRING): '';
        $last_name = ( isset($_POST['last_name']) )? filter_var($_POST['last_name'], FILTER_SANITIZE_STRING): '';

        // check if person already exists in broker_agents table
        $result = BrokerAgent::checkIfAgentAlreadyExists($first_name, $last_name, $broker_id);

        if($result)
        {
            $errorMessage = "Agent with the same first and last name already in system.";

            View::renderTemplate('Error/index.html', [
                'errorMessage' => $errorMessage
            ]);
            exit();
        }

        // check if profile photo included
        if(!empty($_FILES['profile_photo']['tmp_name']))
        {
            // add new agent (with photo) to broker_agents table
            $result = BrokerAgent::postNewAgent($broker_id);

            if($result)
            {
              $message = "Agent successfully added!";

              // display success message
              echo '<script>';
              echo 'alert("'.$message.'")';
              echo '</script>';

              // redirect user to "Manage agents" page
              echo '<script>';
              echo 'window.location.href="/admin/brokers/show-agents?id='.$broker_id.'"';
              echo '</script>';
              exit();
            }
        }
        else
        {
            // add new agent (without) photo to broker_agents table
            $result = BrokerAgent::postNewAgentNoPhoto($broker_id);

            if($result)
            {
              $message = "Agent successfully added!";

              // display success message
              echo '<script>';
              echo 'alert("'.$message.'")';
              echo '</script>';

              // redirect user to "Manage agents" page
              echo '<script>';
              echo 'window.location.href="/admin/brokers/show-agents?id='.$broker_id.'"';
              echo '</script>';
              exit();
            }
        }

    }




    public function showAgents()
    {
      // retrieve GET variable
      $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

      // get agent data
      $agents = BrokerAgent::getAllBrokerAgents($limit=null,$broker_id, $orderby = 'broker_agents.last_name');

      // test
      // echo "<pre>";
      // print_r($agents);
      // echo "</pre>";
      // exit();

      // render view & pass $agents object
      View::renderTemplate('Admin/Show/agents.html', [
          'agents'    => $agents,
          'broker_id' => $broker_id
      ]);
    }




    public function addNewListing()
    {
        // retrieve GET variable
        $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

        // get broker company name
        $broker_company_name = Broker::getBrokerCompanyName($broker_id);

        // get all agents
        $agents = BrokerAgent::getAllBrokerAgents($limit=null, $broker_id, $orderby = 'broker_agents.last_name');

        // get business categories
        $categories = Category::getCategories();

        // get states
        $states = State::getStates();

        // test
        // echo "<pre>";
        // print_r($states);
        // echo "</pre>";
        // exit();

        // render view
        View::renderTemplate('Admin/Add/add-new-listing.html',[
            'agents'              => $agents,
            'categories'          => $categories,
            'states'              => $states,
            'broker_company_name' => $broker_company_name,
            'broker_id'           => $broker_id
        ]);
    }




    public function postNewListing()
    {
        // retrieve GET variable
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // post new listing
        $result = Listing::postNewListing($broker_id);

        if($result)
        {
            $message = "New listing successfully added!";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/show-listings?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
    }




    public function showListings()
    {
        // retrieve GET variable
        $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

        // get listings
        $listings = Listing::getListings($broker_id, $limit = null);

        // test
        // echo $_SESSION['broker_id'];
        // echo "<pre>";
        // print_r($listings);
        // echo "</pre>";
        // exit();

        // get agents id, last name, first name & broker ID only for drop-down
        $agents = BrokerAgent::getNamesOfAllBrokerAgents($broker_id, $orderby='broker_agents.last_name');

        // render view, pass $listings object
        View::renderTemplate('Admin/Show/listings.html', [
            'listings' => $listings,
            'agents'   => $agents
        ]);
    }




    public function showListingsById()
    {
        // retrieve GET variable
        $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

        // get listings
        $listings = Listing::getListingsById($broker_id);

        // test
        // echo "<pre>";
        // print_r($listings);
        // echo "</pre>";
        // exit();

        // get agents id, last name, first name & broker ID only for drop-down
        $agents = BrokerAgent::getNamesOfAllBrokerAgents($broker_id, $orderby = 'broker_agents.last_name');

        // render view, pass $listings object
        View::renderTemplate('Admin/Show/listings.html', [
            'listings' => $listings,
            'agents'   => $agents
        ]);
    }




    public function showListingsByAgentLastName()
    {
        // retrieve GET variable
        $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

        // get listings
        $listings = Listing::getListingsByAgentLastName($broker_id);

        // test
        // echo "<pre>";
        // print_r($listings);
        // echo "</pre>";
        // exit();

        // get agents id, last name, first name & broker ID only for drop-down
        $agents = BrokerAgent::getNamesOfAllBrokerAgents($broker_id, $orderby = 'broker_agents.last_name');

        // render view, pass $listings object
        View::renderTemplate('Admin/Show/listings.html', [
            'listings' => $listings,
            'agents'   => $agents
        ]);

    }




    public function searchListingsByLastNameOrClientId()
    {
        // retrieve form data
        $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
        $input_value = ( isset($_REQUEST['last_name']) ) ?  filter_var($_REQUEST['last_name'], FILTER_SANITIZE_STRING): '';
        $checkbox = ( isset($_REQUEST['clients_id']) ) ?  filter_var($_REQUEST['clients_id'], FILTER_SANITIZE_STRING): '';

        // test
        // echo "Form & URL values:<br>";
        // echo 'broker_id: ' . $broker_id . '<br>';
        // echo 'input value: ' . $input_value . '<br>';
        // echo 'checkbox: ' . $checkbox . '<br><br>';
        // exit();

        // if checkbox is checked, user searching by listing ID
        if($checkbox)
        {
            // assign input value to $client_id & make $last_name = null
            $clients_id = $input_value;
            $last_name = null;
            // echo "If checkbox is checked<br>";
            // echo 'client_id / field input: ' . $clients_id . '<br>';
            // echo 'last_name / should be null: ' . $last_name . '<br><br>';
            // exit();
        }
        if($checkbox == null)
        {
            $last_name = $input_value;
            $clients_id = null;
            // echo "If checkbox not checked<br>";
            // echo 'last_name: ' . $last_name . '<br>';
            // echo 'client_id / should be null: ' . $clients_id . '<br><br>';
            // exit();
        }

        // test
        // echo "After conditional statement:<br>";
        // echo 'broker_id: ' . $broker_id . '<br>';
        // echo 'last_name / value if being searched: ' . $last_name . '<br>';
        // echo 'clients_id / value if being searched: ' . $clients_id . '<br>';
        // exit();

        // get listings and pagetitle
        $results = Listing::getListingsBySearchCriteria($broker_id, $last_name, $clients_id, $limit=null);

        // test
        // echo "<pre>";
        // print_r($results);
        // echo "</pre>";
        // exit();

        // get agents id, last name, first name & broker ID only for drop-down
        $agents = BrokerAgent::getNamesOfAllBrokerAgents($broker_id, $orderby = 'broker_agents.last_name');

        // render view, pass $listings object
        View::renderTemplate('Admin/Show/listings.html', [
            'listings'    => $results['listings'],
            'pagetitle'   => $results['pagetitle'],
            'agents'      => $agents,
            'last_name'   => $last_name
        ]);
    }




    public function searchListingsByClientId()
    {
        // retrieve query string variable
        $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

        // retrieve form data
        $clients_id = ( isset($_REQUEST['clients_id']) ) ?  filter_var($_REQUEST['clients_id'], FILTER_SANITIZE_STRING): '';

        // get listings and pagetitle
        $results = Listing::getListingsBySearchCriteria($broker_id, $last_name=null, $clients_id, $limit=null);

        // test
        // echo "<pre>";
        // print_r($results);
        // echo "</pre>";
        // exit();

        // get agents id, last name, first name & broker ID only for drop-down
        $agents = BrokerAgent::getNamesOfAllBrokerAgents($broker_id, $orderby = 'broker_agents.last_name');

        // render view, pass $listings object
        View::renderTemplate('Admin/Show/listings.html', [
            'listings'    => $results['listings'],
            'pagetitle'   => $results['pagetitle'],
            'agents'      => $agents,
            'last_name'   => $last_name
        ]);
    }





    public function editAgent()
    {
      // retrieve GET variable
      $agent_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

      // get agent data from BrokerAgent model
      $agent = BrokerAgent::getAgent($agent_id);

      // get states for drop-down
      $states = State::getStates();

      // test
      // echo "<pre>";
      // print_r($agent);
      // echo "</pre>";
      // exit();

      View::renderTemplate('Admin/Update/edit-agent.html', [
          'agent' => $agent,
          'states' => $states
      ]);
    }




    public function deleteStateFromAgentProfile()
    {
        // retrieve GET variables
        $state = (isset($_REQUEST['state'])) ? filter_var($_REQUEST['state'], FILTER_SANITIZE_STRING) : '';
        $agent_id = (isset($_REQUEST['agent_id'])) ? filter_var($_REQUEST['agent_id'], FILTER_SANITIZE_STRING) : '';
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // delete state
        $result = Listing::deleteStateAndCounties($state, $agent_id, $broker_id);

        // send result back to Ajax method
        echo $result;
    }




    public function updateAgent()
    {
        // retrieve GET variables
        $agent_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // update agent record
        $result = BrokerAgent::updateAgent($agent_id, $broker_id);

        if($result)
        {
            $message = "Agent data successfully updated!";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/show-agents?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
    }




    public function deleteAgent()
    {
        // retrieve GET variables
        $agent_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';


        // get listings for this agent
        $listings = Listing::getAllAgentListings($agent_id, $limit = null);

        // test
        // echo "<pre>";
        // echo print_r($listings);
        // echo "</pre>";
        // exit();

        if(!empty($listings))
        {
            $errorMessage = "Error. An agent with listings cannot be deleted.
                Please re-assign listings to another agent before attempting to delete.";

            View::renderTemplate('Error/index.html', [
                'errorMessage' => $errorMessage
            ]);
            exit();
        }

        // get profile photo file name
        $profile_photo = BrokerAgent::getProfilePhotoName($agent_id);

        // if profile photo exists delete from server
        if($profile_photo)
        {
            // remove profile photo from server
            $result = BrokerAgent::deleteProfilePhoto($profile_photo);
        }

        // delete agent from broker_agents table
        $result = BrokerAgent::deleteAgent($agent_id);

        // if agent successfully deleted
        if($result)
        {
            $message = "Agent successfully deleted!";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/show-agents?id='.$broker_id.'"';
            echo '</script>';
            exit();

            // optional php redirect - no msg
            // header("Location: /admin/brokers/show-agents?id=$broker_id");
            // exit();
        }
        else
        {
            $message = "Error. Please try again.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';
        }
    }




    public function searchAgents()
    {
        // retrieve query string variable
        $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

        // retrieve form data
        $last_name = (isset($_REQUEST['last_name'])) ? filter_var($_REQUEST['last_name'], FILTER_SANITIZE_STRING) : '';

        // get agent data
        $results = BrokerAgent::getAgents($broker_id, $last_name);

        // test
        // echo "<pre>";
        // print_r($results);
        // echo "</pre>";
        // exit();

        // render view & pass $agents object
        View::renderTemplate('Admin/Show/agents.html', [
            'agents'    => $results['agents'],
            'pagetitle' => $results['pagetitle'],
            'broker_id' => $broker_id
        ]);
    }




    public function updateCompanyProfile()
    {
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // update company data in brokers table
        $result = Broker::updateCompanyProfile($broker_id);

        if($result)
        {
            $message = "Company profile successfully updated!";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/company-profile?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
        else
        {
            $message = "Error. Company data not updated.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/company-profile?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
    }




    public function updateCompanyBrokerPhoto()
    {
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // upload new image
        $result = Broker::updateCompanyBrokerPhoto($broker_id);

        if($result)
        {
            $message = "Broker photo successfully updated!";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/company-profile?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
        else
        {
            $message = "Error. No file chosen.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/company-profile?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
    }




    public function updateCompanyLogo()
    {
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // update company logo
        $result = Broker::updateCompanyLogo($broker_id);

        if($result)
        {
            $message = "Company logo successfully updated!";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/company-profile?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
        else
        {
            $message = "Error. No file chosen.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/company-profile?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
    }




    public function editListing()
    {
        // retrieve GET variable
        $listing_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // get listing data
        $listing = Listing::getListingDetails($listing_id);

        // test
        // echo "<pre>";
        // echo print_r($listing);
        // echo "</pre>";
        // exit();

        // get all agents (for drop-down)
        $agents = BrokerAgent::getAllBrokerAgents($limit=null, $broker_id, $orderby = 'broker_agents.last_name');

        // test
        // echo "<pre>";
        // echo print_r($agents);
        // echo "</pre>";
        // exit();

        // get business categories (for drop-down)
        $categories = Category::getCategories();

        // get broker company name
        $broker_company_name = Broker::getBrokerCompanyName($broker_id);

        // get states for drop-down
        $states = State::getStates();

        // test
        // echo "<pre>";
        // print_r($agent);
        // echo "</pre>";
        // exit();

        View::renderTemplate('Admin/Update/edit-listing.html', [
            'listing'     => $listing,
            'agents'      => $agents,
            'categories'  => $categories,
            'broker_company_name' => $broker_company_name,
            'states'      => $states
        ]);
    }




    public function updateListing()
    {
        // retrieve variable
        $listing_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // update listing
        $result = Listing::updateListing($listing_id, $broker_id);

        if($result)
        {
            $message = "Listing successfully updated!";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/show-listings?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
        else
        {
            $message = "Error. Unable to update listing.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/show-listings?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
    }




    public function editListingImages()
    {
        // retrieve variables - listing ID & broker ID
        $id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // update images
        $result = Listing::updateListingImages($id, $broker_id);

        if($result)
        {
            $message = "Listing images successfully updated!";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user back to this edit page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/edit-listing?id='.$id.'&amp;broker_id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
        else
        {
            $message = "Error. Unable to update listing images.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/show-listings?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
    }




    public function deleteListing()
    {
        // retrieve variable
        $listing_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // delete listing from listing (cascades to listing_financial & listing_images)
        $result = Listing::deleteListing($listing_id);

        if($result)
        {
            $message = "Listing successfully deleted!";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/show-listings?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
        else
        {
            $message = "Error occurred. Listing not deleted.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/show-listings?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
    }




    public function  changePassword()
    {
        // retrieve variable
        $user_id = (isset($_REQUEST['user_id'])) ? filter_var($_REQUEST['user_id'], FILTER_SANITIZE_STRING) : '';

        // update password
        $result = User::updatePassword($user_id);

        // display success alert and logout, or failure alert & redirect
        if($result)
        {
            $message = "Your password was successfully changed! You must log back in.";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/logout"';
            echo '</script>';
            exit();
        }
        else
        {
            $message = "Error occurred. Please try again.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/index?user_id='.$user_id.'"';
            echo '</script>';
            exit();
        }
    }




    public function changeLoginEmail()
    {
        // retrieve variable
        $user_id = (isset($_REQUEST['user_id'])) ? filter_var($_REQUEST['user_id'], FILTER_SANITIZE_STRING) : '';

        // update password
        $result = User::updateLoginEmail($user_id);

        // display success alert and logout, or failure alert & redirect
        if($result)
        {
            $message = "Your login email was successfully changed! You must log back in.";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/logout"';
            echo '</script>';
            exit();
        }
        else
        {
            $message = "Error occurred. Please try again.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/index?user_id='.$user_id.'"';
            echo '</script>';
            exit();
        }
    }




    public function deleteListingImage()
    {
        // retrieve variable
        $listing_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';
        $image = (isset($_REQUEST['image'])) ? filter_var($_REQUEST['image'], FILTER_SANITIZE_STRING) : '';

        // delete from listing_images table
        $result = Listing::deleteListingImage($listing_id, $image);

        // display success alert and logout, or failure alert & redirect
        if($result)
        {
            $message = "Image successfully deleted.";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            //echo 'window.location.href="/admin/brokers/show-listings?id='.$broker_id.'"';
            echo 'window.location.href="/admin/brokers/edit-listing?id='.$listing_id.'&amp;broker_id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
        else
        {
            $message = "Error occurred. Please try again.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user back to this page to see failed delete
            echo '<script>';
            echo 'window.location.href="/admin/brokers/edit-listing?id='.$listing_id.'&amp;broker_id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
    }



    public function addNewRealEstateListing()
    {
        // retrieve GET variable
        $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

        // get broker company name
        $broker_company_name = Broker::getBrokerCompanyName($broker_id);

        // get all agents
        $agents = BrokerAgent::getAllBrokerAgents($limit=null, $broker_id, $orderby = 'broker_agents.last_name');

        // get states
        $states = State::getStates();

        // for sale categories array
        // $for_sale_categories = [
        //     "All categories",
        //     "Multi-family",
        //     "Retail",
        //     "Industrial",
        //     "Office",
        //     "Income Business",
        //     "Entertainment",
        //     "Hospitality",
        //     "Medical",
        //     "Worship",
        //     "Land",
        //     "Other"
        // ];

        // for sale categories array
        // $for_lease_categories = [
        //     "All categories",
        //     "Retail",
        //     "Industrial",
        //     "Office",
        //     "Medical",
        //     "Short Term Office",
        //     "Other"
        // ];

        // render view
        View::renderTemplate('Admin/Add/add-new-real-estate-listing.html',[
            'agents'                => $agents,
            'states'                => $states,
            'broker_company_name'   => $broker_company_name,
            'broker_id'             => $broker_id
            // 'for_sale_categories'   => $for_sale_categories,
            // 'for_lease_categories'  => $for_lease_categories
        ]);
    }



    public function showRealEstateListings()
    {
        // retrieve GET variable
        $broker_id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';

        // get listings
        $listings = Realtylisting::getListings($broker_id, $id=null, $limit = null);

        // test
        // echo $_SESSION['broker_id'];
        // echo "<pre>";
        // print_r($listings);
        // echo "</pre>";
        // exit();

        // get agents by type
        //$agents = BrokerAgent::getRealtyBrokerAgents($limit=null, $broker_id, $type=['2','3'], $orderby = 'broker_agents.last_name');
        $agents = BrokerAgent::getNamesOfAllBrokerAgents($broker_id, $orderby='broker_agents.last_name');

        // render view, pass $listings object
        View::renderTemplate('Admin/Show/realty-listings.html', [
            'listings' => $listings,
            'agents'   => $agents
        ]);
    }



    public function postNewRealEstateListing()
    {
        // retrieve GET variable
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // post new listing
        $result = Realtylisting::postNewRealEstateListing($broker_id);

        if($result)
        {
            $message = "New real estate listing successfully added!";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/show-real-estate-listings?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
    }



    public function deleteRealEstateListing()
    {
        // retrieve ID from query string
        $id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // delete listing from realty-listings table
        $result = Realtylisting::deleteRealEstateListing($id);

        // if listing successfully deleted
        if($result)
        {
            $message = "Real estate listing successfully deleted!";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/show-real-estate-listings?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
        else
        {
            $message = "Error. Please try again.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';
        }
    }


    /**
     * edit real estate listing in realty_listings table
     *
     * @return boolean
     */
    public function editRealEstateListing()
    {
        // retrieve GET variable from query string
        $id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // get lisgin data
        $listing = Realtylisting::getRealEstateListing($id);

        // test
        // echo "<pre>";
        // print_r($listing);
        // echo "</pre>";
        // exit();

        // get states for drop-down
        $states = State::getStates();

        // get broker company name
        $broker_company_name = Broker::getBrokerCompanyName($broker_id);

        // get agents for drop-down
        $agents = BrokerAgent::getAllBrokerAgents($limit=null, $broker_id, $orderby = 'broker_agents.last_name');

        // test
        // echo "<pre>";
        // print_r($agents);
        // echo "</pre>";
        // exit();

        // for sale categories array
        $for_sale_categories = [
            "All categories",
            "Multi-family",
            "Retail",
            "Industrial",
            "Office",
            "Income Business",
            "Entertainment",
            "Hospitality",
            "Medical",
            "Worship",
            "Land",
            "Other"
        ];

        // for sale categories array
        $for_lease_categories = [
            "All categories",
            "Retail",
            "Industrial",
            "Office",
            "Medical",
            "Short Term Office",
            "Other"
        ];

        View::renderTemplate('Admin/Update/edit-real-estate-listing.html', [
            'listing'               => $listing,
            'states'                => $states,
            'agents'                => $agents,
            'for_sale_categories'   => $for_sale_categories,
            'for_lease_categories'  => $for_lease_categories,
            'broker_company_name'   => $broker_company_name
        ]);
    }


    /**
     * updates data for specified record
     *
     * @return boolean
     */
    public function updateRealEstateListing()
    {
        // retrieve get variable data from query string
        $id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // update listing
        $result = Realtylisting::updateRealEstateListing($id, $broker_id);

        if($result)
        {
            $message = "Real estate listing successfully updated!";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/show-real-estate-listings?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
        else
        {
            $message = "Error. Unable to update listing.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            echo 'window.location.href="/admin/brokers/show-real-estate-listings?id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
    }


    /**
     * deletes specified real estate listing image for specified record by ID
     *
     * @return boolean
     */
    public function deleteRealEstateListingImage()
    {
        // retrieve variable
        $id = (isset($_REQUEST['id'])) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
        $image = (isset($_REQUEST['image'])) ? filter_var($_REQUEST['image'], FILTER_SANITIZE_STRING) : '';
        $broker_id = (isset($_REQUEST['broker_id'])) ? filter_var($_REQUEST['broker_id'], FILTER_SANITIZE_STRING) : '';

        // delete from listing_images table
        $result = Realtylisting::deleteListingImage($id, $image);

        // display success alert and logout, or failure alert & redirect
        if($result)
        {
            $message = "Image successfully deleted.";

            // display success message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user to "Manage agents" page
            echo '<script>';
            //echo 'window.location.href="/admin/brokers/show-listings?id='.$broker_id.'"';
            echo 'window.location.href="/admin/brokers/edit-real-estate-listing?id='.$id.'&amp;broker_id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
        else
        {
            $message = "Error occurred. Please try again.";

            // display error message
            echo '<script>';
            echo 'alert("'.$message.'")';
            echo '</script>';

            // redirect user back to this page to see failed delete
            echo '<script>';
            echo 'window.location.href="/admin/brokers/edit-real-estate-listing?id='.$id.'&amp;broker_id='.$broker_id.'"';
            echo '</script>';
            exit();
        }
    }

}