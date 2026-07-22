import { createFileRoute } from '@tanstack/react-router'
import { WeatherPhrases } from '@/features/weather-phrases'

export const Route = createFileRoute('/_authenticated/weather-phrases/')({
  component: WeatherPhrases,
})
