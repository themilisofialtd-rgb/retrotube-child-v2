# Retrotube Child (Flipbox Edition) v2

**What’s new**
- Sidebar on the Models page template.
- Pagination for Actors grid (16 per page by default).
- Banner slot (364×45) injected between the first 8 and next 8 models.
- Trigger button on each flipbox front (“Watch now” by default).
- Actor bio text is now **under** the hero image.
- Optional social links on actor profile (OnlyFans, FanCentro, Reddit, Facebook, Instagram, Twitter/X, TikTok, Website).
- New `[promo_flipboxes]` shortcode for 4 external flipboxes.

## Install / Update
1. Upload and activate the theme: Appearance → Themes → Upload → `retrotube-child-v2.zip`.
2. Make sure the parent theme folder is named **retrotube**.

## Models page
Create a page “Models” and choose **Template: Models Flipboxes (with Sidebar)**.  
This uses shortcode: `[actors_flipboxes per_page="16" cols="4" show_pagination="true"]`.

### Banner between 8th and 9th item
Edit the file: `assets/models-banner.html` and paste your ad code (364×45).  
Alternatively pass attributes on the shortcode:
```
[actors_flipboxes per_page="16"
  banner_img="/wp-content/uploads/your-ad.webp"
  banner_url="https://your-link.com"
  banner_alt="Sponsored"]
```

## Shortcodes

### Actors grid (with pagination)
```
[actors_flipboxes per_page="16" cols="4" trigger_text="Watch now" show_pagination="true"]
```
- Pagination uses `?pg=2` on the URL.
- Other params: `orderby`, `order`, `hide_empty` (true/false), `page_var` (default `pg`).

### Promo flipboxes (4 external)
```
[promo_flipboxes
  front1="" back1="" url1="" title1=""
  front2="" back2="" url2="" title2=""
  front3="" back3="" url3="" title3=""
  front4="" back4="" url4="" title4=""
  cols="4"]
```

## Actor profile (taxonomy-actors.php)
- Hero image: ACF field `hero_image` (optional).  
- Bio (under the image): `short_bio`.  
- Live button: `live_link`.  
- Optional social links (any may be empty): `link_website`, `link_onlyfans`, `link_fancentro`, `link_reddit`, `link_facebook`, `link_instagram`, `link_twitter`, `link_tiktok`.

## ACF fields (add to taxonomy `actors`)
- `actor_card_front` (Image) – Flip front
- `actor_card_back`  (Image) – Flip back
- `hero_image` (Image)
- `short_bio` (Textarea)
- `live_link` (URL)
- `link_website` (URL), `link_onlyfans` (URL), `link_fancentro` (URL), `link_reddit` (URL),
  `link_facebook` (URL), `link_instagram` (URL), `link_twitter` (URL), `link_tiktok` (URL)

Import the provided **acf-actor-fields-v2.json** via ACF → Tools → Import.