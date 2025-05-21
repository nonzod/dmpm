<?php

namespace Drupal\myportal_user\Plugin\Block;

use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\social_user\Plugin\Block\AccountHeaderBlock;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\myportal_tour\TourPageCheckInterface;
use Drupal\myaccess\ApplicationsManagerInterface;
use Drupal\myaccess\UserManagerInterface;

/**
 * Provides a 'AccountHeader' block.
 *
 * @Block(
 *   id = "myp_account_header_block",
 *   admin_label = @Translation("My Portal Account header block"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class AccountHeader extends AccountHeaderBlock implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatcher;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The tour page check.
   *
   * @var \Drupal\myportal_tour\TourPageCheckInterface
   */
  protected $tourPageCheck;

  /**
   * The MyLinks manager.
   *
   * @var \Drupal\myaccess\ApplicationsManagerInterface
   */
  protected $appMyLinks;

  /**
   * The User manager.
   *
   * @var \Drupal\myaccess\UserManagerInterface
   */
  protected $userManager;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $route_matcher = $container->get('current_route_match');
    assert($route_matcher instanceof RouteMatchInterface);
    $instance->routeMatcher = $route_matcher;

    $path_matcher = $container->get('path.matcher');
    assert($path_matcher instanceof PathMatcherInterface);
    $instance->pathMatcher = $path_matcher;

    $tour_page_check = $container->get('myportal_tour.tour_page_check');
    assert($tour_page_check instanceof TourPageCheckInterface);
    $instance->tourPageCheck = $tour_page_check;

    $app_mylinks_check = $container->get('myaccess.applications_manager');
    assert($app_mylinks_check instanceof ApplicationsManagerInterface);
    $instance->appMyLinks = $app_mylinks_check;

    $user_manager = $container->get('myaccess.user_manager');
    assert($user_manager instanceof UserManagerInterface);
    $instance->userManager = $user_manager;

    $state = $container->get('state');
    assert($state instanceof StateInterface);
    $instance->state = $state;

    return $instance;
  }

  /**
   * Overrides the extended block build.
   *
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
  public function build() {
    $block = parent::build();

    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->getContextValue('user');

    // Remove link.
    $block['menu_items']['#items']['account_box']['signed_in_as']['#template'] = "{{tagline}}<strong class='text-truncate'>{{object}}</strong>";

    // Remove items menus user_account_menu.
    unset($block['menu_items']['#items']['account_box']['my_invites']);
    unset($block['menu_items']['#items']['account_box']['divider_mobile']);
    unset($block['menu_items']['#items']['account_box']['divider_no_mobile']);
    unset($block['menu_items']['#items']['account_box']['my_events']);
    unset($block['menu_items']['#items']['account_box']['my_groups']);
    unset($block['menu_items']['#items']['account_box']['my_topics']);
    unset($block['menu_items']['#items']['account_box']['divider_account']);
    unset($block['menu_items']['#items']['account_box']['my_account']);
    unset($block['menu_items']['#items']['account_box']['divider_profle']);
    unset($block['menu_items']['#items']['account_box']['edit_profile']);
    unset($block['menu_items']['#items']['account_box']['my_profile']);
    unset($block['menu_items']['#items']['account_box']['divider_content']);
    unset($block['menu_items']['#items']['account_box']['divider_logout']);
    unset($block['menu_items']['#items']['account_box']['my_content']);

    if ($account->isAuthenticated()) {
      $block['menu_items']['#items']['search_site'] = [
        '#type' => 'account_header_element',
        '#title' => $this->t('Search site'),
        '#url' => Url::fromRoute('view.search_site.page_1'),
        '#label' => $this->t('Search site'),
        '#weight' => 100,
        '#icon' => 'search',
        '#wrapper_attributes' => [
          'class' => ['account__search_site'],
        ],
        '#cache' => [
          'contexts' => 'user',
        ],
      ];
      $block['menu_items']['#items']['menarini_channel'] = [
        '#type' => 'account_header_element',
        '#title' => $this->t('Menarini Channel'),
        '#url' => Url::fromRoute('view.events_list.channel_streaming'),
        '#label' => $this->t('Menarini Channel'),
        '#wrapper_attributes' => [
          'class' => ['account__menarini-channel'],
        ],
        '#cache' => [
          'contexts' => 'user',
        ],
      ];
      $block['menu_items']['#items']['google_app'] = [
        '#type' => 'account_header_element',
        '#title' => $this->t('Google App'),
        '#url' => Url::fromRoute('myaccess.applications-google'),
        '#label' => $this->t('Google App'),
        '#wrapper_attributes' => [
          'class' => ['account__google_app'],
        ],
        '#link_attributes' => [
          'class' => ['use-ajax-fullscreen'],
        ],
        '#cache' => [
          'contexts' => 'user',
        ],
      ];
      $block['menu_items']['#items']['myapp'] = [
        '#type' => 'account_header_element',
        '#title' => $this->t('My App'),
        '#url' => Url::fromRoute('myaccess.applications-grid'),
        '#label' => $this->t('My App'),
        '#weight' => 500,
        '#wrapper_attributes' => [
          'class' => ['account__myapp'],
        ],
        '#link_attributes' => [
          'class' => ['use-ajax-fullscreen'],
        ],
        '#cache' => [
          'contexts' => 'user',
        ],
      ];

      $application_list = $this->appMyLinks->getMyLinksApplications($this->userManager->getCurrentDrupalUser());
      if (!empty($application_list)) {
        $block['menu_items']['#items']['mylinks'] = [
          '#type' => 'account_header_element',
          '#title' => $this->t('My Links'),
          '#url' => Url::fromRoute('myaccess.applications-mylinks'),
          '#label' => $this->t('My Links'),
          '#wrapper_attributes' => [
            'class' => ['account__mylinks'],
          ],
          '#link_attributes' => [
            'class' => ['use-ajax-fullscreen'],
          ],
          '#cache' => [
            'contexts' => 'user',
          ],
        ];
      }

      $tech_help_data = $this->getTechHelpData();
      if(!empty($tech_help_data)){
        $th_nid = $tech_help_data['link'];
        $user = $this->userManager->getCurrentDrupalUser();
        $th_node = \Drupal::entityTypeManager()->getStorage('node')->load($th_nid);
        if($th_node->access('view', $user)){
          //$url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/'.$th_nid);
          $block['menu_items']['#items']['tech_help'] = [
            '#type' => 'account_header_element',
            '#title' => $tech_help_data['label'],
            '#url' => $th_node->toUrl(),
            '#label' => $tech_help_data['label'],
            '#weight' => 502,
            '#wrapper_attributes' => [
              'class' => ['account__techhelp'],
            ],
            '#cache' => [
              'contexts' => 'user',
            ],
          ];
        }
      }

      if ($this->tourPageCheck->issetTourPage()) {
        $block['menu_items']['#items']['help'] = [
          '#type' => 'account_header_element',
          '#title' => $this->t('Help'),
          '#label' => $this->t('Help'),
          '#wrapper_attributes' => [
            'id' => ['account__help'],
            'class' => ['tour-toolbar-tab', 'hidden', 'js-tour-start-button', 'account__help'],
          ],
          '#attributes' => [
            'class' => ['toolbar-icon', 'toolbar-icon-help'],
          ],
          '#attached' => [
            'library' => [
              'tour/tour',
            ],
          ],
          '#cache' => [
            'contexts' => [
              'url.path',
              'url.query_args',
            ],
          ],
        ];
      }
    }

    return $block;
  }

  private function getTechHelpData() {
    $myp_config = \Drupal::config('myportal.tech_help.settings');
    $data = [];
    if($myp_config->get('tech_help_link_active')) {
      /** @var  $langManager \Drupal\Core\Language\LanguageManagerInterface */
      $langManager = \Drupal::languageManager();
      $data['link'] = $myp_config->get('tech_help_link');
      $curr_lang = $langManager->getCurrentLanguage()->getId();
      if(!empty($myp_config->get("tech_help_label_{$curr_lang}"))){
        $data['label'] = $myp_config->get("tech_help_label_{$curr_lang}");
      } elseif (!empty($myp_config->get('tech_help_label_en'))) {
        $data['label'] = $myp_config->get('tech_help_label_en');
      } else {
        $data['label'] = 'Tech Help';
      }
    }
    return $data;
  }

}
