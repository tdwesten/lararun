# Lararun New Features 🎯

This document describes the new features added to Lararun to improve the running experience and coach quality.

## 1. Enhanced Runner Profile 👤

### Description
Users can now provide detailed information about themselves to help the AI coach create more personalized training plans.

### Profile Fields
- **Age**: Helps tailor training intensity and recovery recommendations
- **Weight (kg)**: Used for more accurate calorie and effort calculations
- **Fitness Level**: Choose from Beginner, Intermediate, Advanced, or Elite
- **Injury History**: Document past injuries so the coach can avoid aggravating them
- **Training Preferences**: Share what you enjoy (e.g., intervals, long runs, hill workouts)

### How It Works
The AI coach uses this profile information in combination with your activity history to generate safer, more effective training plans. For example:
- Beginners get more gradual progression
- Athletes with knee issues receive low-impact alternatives
- Your age influences recovery time recommendations

### Access
Navigate to **Settings → Profile** to update your runner profile.

---

## 2. Workout Feedback System 💬

### Description
Provide feedback on your completed workouts to help the AI coach learn your preferences and adjust future recommendations.

### Feedback Options
- **Status**: Completed, Partially Completed, or Skipped
- **Difficulty Rating**: 1-5 scale (1 = too easy, 5 = too hard)
- **Enjoyment Rating**: 1-5 scale
- **Notes**: Free-form text for additional context

### How It Works
When you provide feedback:
1. Click the "Feedback" button on any current or past workout recommendation
2. Rate your experience
3. The AI coach incorporates this into future training plan generation
4. Plans become more aligned with your preferences over time

### Benefits
- Workouts become better matched to your actual fitness level
- Coach learns which types of runs you enjoy most
- Difficulty is automatically adjusted based on your feedback

---

## 3. Personal Records Tracking 🏆

### Description
Lararun automatically tracks and displays your personal bests across various distances and metrics.

### Tracked Records
- **Fastest 5K**: Your best 5 kilometer time
- **Fastest 10K**: Your best 10 kilometer time
- **Fastest Half Marathon**: Your best 21.0975 km time
- **Fastest Marathon**: Your best 42.195 km time
- **Longest Run**: Your furthest distance
- **Fastest Pace**: Your best pace per kilometer (for runs ≥1km)

### How It Works
- Records are automatically updated when you complete activities
- Each record shows the date achieved
- Only applies to runs within 5% tolerance of the target distance
- Displayed on your dashboard in the Personal Records widget

### Benefits
- Visualize your progress over time
- Celebrate achievements
- Stay motivated with clear goals

---

## 4. Activity Streak Counter 🔥

### Description
Track consecutive days with running activity to build consistency and maintain momentum.

### Features
- Shows current streak in days
- "On fire!" badge when you reach 7+ days
- Displayed prominently on the dashboard
- Visual flame icon that changes color based on streak

### How It Works
- Streak increases each day you complete a run
- Resets to 0 when you miss a day
- Only counts days with actual activity (not rest days)

### Benefits
- Gamification encourages consistency
- Visual feedback for maintaining habits
- Helps build long-term running discipline

---

## 5. Recovery Score & Insights 💪

### Description
Real-time recovery tracking helps you understand when to push hard and when to take it easy.

### Recovery Metrics
- **Recovery Score (0-10)**: Overall readiness to train
  - 8-10: Fully recovered, ready for intense workouts
  - 6-8: Good recovery, normal training appropriate
  - 4-6: Moderate recovery, consider easier sessions
  - 0-4: Need rest, take it easy

- **Estimated Recovery Hours**: Time needed to fully recover from each run

### How It's Calculated
Recovery score considers:
- Recent activity intensity (from heart rate zones)
- Time since last workout
- Distance and duration of recent runs
- Cumulative fatigue over the past 7 days

### Benefits
- Prevent overtraining and injury
- Optimize training timing
- Better understand your body's recovery needs
- AI coach uses recovery data to adjust recommendations

---

## 6. Improved Dashboard Layout 📊

### Description
A redesigned dashboard with better data visualization and more actionable insights.

### New Layout
**Top Row - 5 Quick Stats Widgets:**
1. Current Objective
2. Today's Recommendation
3. Last Run
4. Activity Streak
5. Recovery Score

**Bottom Section:**
- **Left (2/3)**: Recent Activities list
- **Right (1/3)**: Personal Records sidebar

### Benefits
- All key information visible at a glance
- Better use of screen space
- Quick access to most relevant data
- Mobile-responsive design

---

## 7. Enhanced AI Coach Context 🤖

### Description
The AI coach now has access to much more information about you, leading to better training plans.

### Additional Context Includes
- Your runner profile (age, weight, fitness level, injuries, preferences)
- Current recovery score
- Recent workout feedback (difficulty, enjoyment)
- Recovery status from past activities
- Personal records and progress trends

### Impact on Training Plans
- **Personalization**: Plans match your specific needs and constraints
- **Safety**: Considers injuries and recovery status
- **Effectiveness**: Adjusted based on feedback and actual performance
- **Enjoyment**: Incorporates preferred workout types
- **Progression**: Appropriate for your fitness level

### Example Improvements
- "Given your knee injury history, I'm recommending low-impact cross-training today instead of intervals"
- "Your recovery score is 4/10, so today's run will be easy and shorter"
- "Based on your feedback that hill workouts are too hard, I've adjusted the gradient recommendations"

---

## Getting Started with New Features

### Step 1: Complete Your Runner Profile
1. Go to **Settings → Profile**
2. Scroll to "Runner Profile" section
3. Fill in your age, weight, fitness level
4. Add any injury history or training preferences
5. Click "Save"

### Step 2: Provide Workout Feedback
1. After completing a workout, go to **Objectives → Your Active Objective**
2. Find today's or yesterday's recommendation
3. Click the "Feedback" button
4. Rate the workout and add notes
5. Submit

### Step 3: Monitor Your Progress
1. Check your dashboard daily
2. Track your activity streak
3. Monitor your recovery score
4. Celebrate new personal records!

---

## Technical Implementation

### Database Changes
- Added 5 new fields to `users` table
- Created `workout_feedback` table
- Created `personal_records` table
- Added recovery fields to `activities` table

### New Models & Services
- `WorkoutFeedback` model
- `PersonalRecord` model
- `PersonalRecordService` for automatic record detection
- `DailyRecommendationPolicy` for authorization

### UI Components
- `ActivityStreakWidget`
- `RecoveryScoreWidget`
- `PersonalRecordsWidget`
- `WorkoutFeedbackModal`
- `Progress` component

### Enhanced Logic
- `ActivityObserver` now calculates recovery and checks for personal records
- `GenerateWeeklyTrainingPlanJob` includes user profile and recovery data in AI prompts
- Dashboard controller provides streak, recovery, and records data

---

## Future Enhancements

Potential additions for future versions:

1. **Weekly/Monthly Progress Charts**: Visual graphs of distance, pace, and improvement over time
2. **Training Load Management**: Scientific TSS (Training Stress Score) calculations
3. **Race Predictions**: AI-powered race time predictions based on current fitness
4. **Social Features**: Share achievements and compete with friends
5. **Integration with More Devices**: Connect with Garmin, Apple Watch, etc.
6. **Nutrition Tracking**: Log meals and get nutrition recommendations
7. **Sleep Tracking**: Factor sleep quality into recovery scores
8. **Weather Integration**: Adjust recommendations based on weather conditions

---

## Support & Feedback

If you encounter any issues or have suggestions for these new features, please:
- Open an issue on GitHub
- Contact the development team
- Provide feedback through the workout feedback system

Happy running! 🏃‍♂️💨
