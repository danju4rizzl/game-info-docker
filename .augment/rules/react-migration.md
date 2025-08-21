---
type: 'manual'
---

**Prompt:**

You are a senior WordPress and React developer. Your task is to refactor an existing PHP-based WordPress plugin into a scalable application using **React** for the frontend and **PHP** for the backend. The current plugin, named "Panda Score API Tracker," fetches and displays esports match data.

**Here is the existing PHP code:**

```php
<?php
/*
Plugin Name: Panda Score API Tracker
Description: Fetches and displays PandaScore game scores via shortcode. Right-aligned by default.
Version: 1.4
Author: Deejay Dev
Text Domain: pandascore-tracker
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ... (existing PHP code as provided by the user) ...

?>
```

**Refactoring Requirements:**

1.  **Backend (PHP):**

    - **Keep all existing functionality:** Maintain the activation/deactivation hooks, admin settings page, and API key management.
    - **Remove front-end logic:** Eliminate all HTML generation and inline CSS from the `shortcode_handler` and `add_inline_styles` functions.
    - **Create a custom WordPress REST API endpoint:** This endpoint should be a single source of truth for all match data. It should accept parameters for `game` and `limit`, and be able to be extended in the future for `live` and `league` filtering. The endpoint should handle the API key authorization and the `wp_remote_get` request to the PandaScore API.
    - **Update the shortcode handler:** The `shortcode_handler` should now be a simple container. It should enqueue the React application's JavaScript and CSS files and render a `div` element with an `id` (e.g., `pandascore-tracker-root`). It must pass shortcode attributes like `game`, `limit`, and `title` to the React app via `data-` attributes on this `div`.
    - **Security:** Use `wp_localize_script` to securely pass a nonce to the React app for authenticated requests to the REST API endpoint.

2.  **Frontend (React):**

    - **Create a new React application:** Build a single-page application (SPA) that will be rendered inside the `pandascore-tracker-root` container.
    - **Fetch data from the new endpoint:** The React app must use `wp.apiFetch` to get match data from the custom REST API endpoint you created.
    - **Component-based UI:** The UI should be built using a **component-based approach**. This is crucial for scalability as we plan to add features like live matches and league sorting.
    - **Design and Styling:**
      - **Implement the exact UI and style from the existing plugin code.** Pay close attention to the visual elements, layout, and colors.
      - Use **Shadcn UI** components and **Tailwind CSS** for all styling. Do not use any custom CSS files. All styling must be done with Tailwind utility classes.
      - The design must be responsive, replicating the behavior of the existing `@media (max-width: 480px)` styles.

3.  **Build Process:**

    - Provide a basic **Webpack** configuration file. This config should compile the React JSX code into a single, production-ready JavaScript bundle.
    - Include instructions for the user on how to install necessary dependencies and run the build command (`npm run build`).

**Future-proofing and Scalability:**

The new architecture must be designed with future growth in mind. Specifically, the component structure and API endpoint should be easily extendable to include:

- Displaying **live match scores** with real-time updates.
- **Filtering and sorting** matches by league.

The refactored code should be clean, well-commented, and adhere to modern development best practices for both WordPress and React.
