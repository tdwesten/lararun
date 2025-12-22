# Lararun 🏃‍♂️ v0.1.0-beta
<img width="2686" height="1612" alt="CleanShot 2025-12-22 at 17 04 02@2x" src="https://github.com/user-attachments/assets/9675b3f0-a700-4ae7-83fd-e81d4ea5c2fd" />

Lararun is a self-hosted AI running coach built with Laravel, designed to help you reach your running goals with personalized, data-driven training plans. By syncing your Strava activities and leveraging OpenAI's advanced reasoning (via Prism), Lararun provides daily workout recommendations tailored to your current fitness level and specific objectives.

## Features
- **Strava Integration**: Automatically syncs your latest running activities.
- **AI-Powered Coaching**: Uses OpenAI to generate context-aware training recommendations.
- **Objective Management**: Set goals like "Run 5K", "Run 10K", "Run Half Marathon", or "Run Faster".
- **Daily Recommendations**: Receive personalized workout suggestions every day.
- **Email Notifications**: Stay on track with daily emails via Lettermint.
- **Self-Hosted**: You own your data and your coach.

## Installation

Follow these steps to get Lararun running on your own server:

1. **Clone the repository**
   ```bash
   git clone https://github.com/thomasvanderwesten/lararun.git
   cd lararun
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   # Create a sqlite database (or use your preferred DB)
   touch database/database.sqlite
   php artisan migrate
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **API Keys Configuration**
   Edit your `.env` file and add the following required keys:
   - `OPENAI_API_KEY`: Your OpenAI API key for generating recommendations.
   - `STRAVA_CLIENT_ID`: Your Strava Application Client ID.
   - `STRAVA_CLIENT_SECRET`: Your Strava Application Client Secret.
   - `STRAVA_REDIRECT_URI`: Should match `http://your-domain.com/auth/strava/callback`.
   - `LETTERMINT_TOKEN`: Required if you want to receive daily training emails.

7. **Queue and Scheduler**
   Ensure your queue worker is running and the scheduler is set up:
   ```bash
   php artisan queue:work
   # Add to crontab: * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

---
Lararun is currently in **Beta**. Contributions and feedback are welcome!
