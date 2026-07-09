'use client'

import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { SelectDropdown } from '@/components/select-dropdown'
import { Switch } from '@/components/ui/switch'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Textarea } from '@/components/ui/textarea'
import { budgetTags, difficulties, sources } from '../data/data'
import { type Recipe } from '../data/schema'
import { useCreateRecipe, useUpdateRecipe } from '../hooks/use-recipes'
import { RecipeIngredientsEditor } from './recipe-ingredients-editor'

const NONE_VALUE = '__none__'

const difficultyItems = [
  { label: 'Not set', value: NONE_VALUE },
  ...difficulties.map(({ label, value }) => ({ label, value })),
]

const toNumberOrNull = (v?: string) =>
  v && v.trim() !== '' ? Number(v) : null

const toLines = (v?: string) =>
  v
    ? v
        .split('\n')
        .map((s) => s.trim())
        .filter(Boolean)
    : []

const fromLines = (arr?: string[] | null) =>
  arr && arr.length > 0 ? arr.join('\n') : ''

const formSchema = z.object({
  title: z.string().min(1, 'Title is required.'),
  description: z.string().optional(),
  category: z.string().optional(),
  source: z.string().min(1, 'Source is required.'),
  budget_tag: z.string().min(1, 'Budget tag is required.'),
  user_id: z.string().optional(),
  estimated_cost: z.string().optional(),
  servings: z.string().optional(),
  prep_time_minutes: z.string().optional(),
  cook_time_minutes: z.string().optional(),
  difficulty: z.string().optional(),
  image_url: z.string().optional(),
  is_published: z.boolean(),
  is_premium_only: z.boolean(),
  steps: z.string().optional(),
  tips: z.string().optional(),
  tags: z.string().optional(),
  dietary_flags: z.string().optional(),
})
type RecipeForm = z.infer<typeof formSchema>

type RecipeActionDialogProps = {
  currentRow?: Recipe
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function RecipesActionDialog({
  currentRow,
  open,
  onOpenChange,
}: RecipeActionDialogProps) {
  const isEdit = !!currentRow
  const { mutate: createRecipe, isPending: creating } = useCreateRecipe()
  const { mutate: updateRecipe, isPending: updating } = useUpdateRecipe()
  const isPending = creating || updating

  const form = useForm<RecipeForm>({
    resolver: zodResolver(formSchema),
    defaultValues: isEdit
      ? {
          title: currentRow.title,
          description: currentRow.description ?? '',
          category: currentRow.category ?? '',
          source: currentRow.source,
          budget_tag: currentRow.budget_tag,
          user_id:
            currentRow.user_id != null ? String(currentRow.user_id) : '',
          estimated_cost: currentRow.estimated_cost ?? '',
          servings:
            currentRow.servings != null ? String(currentRow.servings) : '',
          prep_time_minutes:
            currentRow.prep_time_minutes != null
              ? String(currentRow.prep_time_minutes)
              : '',
          cook_time_minutes:
            currentRow.cook_time_minutes != null
              ? String(currentRow.cook_time_minutes)
              : '',
          difficulty: currentRow.difficulty ?? NONE_VALUE,
          image_url: currentRow.image_url ?? '',
          is_published: currentRow.is_published,
          is_premium_only: currentRow.is_premium_only,
          steps: fromLines(currentRow.steps),
          tips: fromLines(currentRow.tips),
          tags: fromLines(currentRow.tags),
          dietary_flags: fromLines(currentRow.dietary_flags),
        }
      : {
          title: '',
          description: '',
          category: '',
          source: 'admin',
          budget_tag: 'budget_100',
          user_id: '',
          estimated_cost: '',
          servings: '',
          prep_time_minutes: '',
          cook_time_minutes: '',
          difficulty: NONE_VALUE,
          image_url: '',
          is_published: false,
          is_premium_only: false,
          steps: '',
          tips: '',
          tags: '',
          dietary_flags: '',
        },
  })

  const onSubmit = (values: RecipeForm) => {
    const onSuccess = () => {
      form.reset()
      onOpenChange(false)
      toast.success(isEdit ? 'Recipe updated.' : 'Recipe created.')
    }
    const onError = (error: any) => {
      toast.error(error?.response?.data?.message ?? 'Could not save recipe.')
    }

    const payload = {
      title: values.title,
      description: values.description?.trim() || null,
      category: values.category?.trim() || null,
      source: values.source,
      budget_tag: values.budget_tag,
      user_id: toNumberOrNull(values.user_id),
      estimated_cost: toNumberOrNull(values.estimated_cost),
      servings: values.servings ? Number(values.servings) : null,
      prep_time_minutes: values.prep_time_minutes
        ? Number(values.prep_time_minutes)
        : null,
      cook_time_minutes: values.cook_time_minutes
        ? Number(values.cook_time_minutes)
        : null,
      difficulty:
        values.difficulty && values.difficulty !== NONE_VALUE
          ? values.difficulty
          : null,
      image_url: values.image_url?.trim() || null,
      is_published: values.is_published,
      is_premium_only: values.is_premium_only,
      steps: toLines(values.steps),
      tips: toLines(values.tips),
      tags: toLines(values.tags),
      dietary_flags: toLines(values.dietary_flags),
    }

    if (isEdit) {
      updateRecipe({ id: currentRow.id, ...payload }, { onSuccess, onError })
    } else {
      createRecipe(payload, { onSuccess, onError })
    }
  }

  return (
    <Dialog
      open={open}
      onOpenChange={(state) => {
        form.reset()
        onOpenChange(state)
      }}
    >
      <DialogContent className='sm:max-w-3xl'>
        <DialogHeader className='text-start'>
          <DialogTitle>{isEdit ? 'Edit Recipe' : 'Add New Recipe'}</DialogTitle>
          <DialogDescription>
            {isEdit ? 'Update the recipe here. ' : 'Create a new recipe here. '}
            Click save when you&apos;re done.
          </DialogDescription>
        </DialogHeader>
        <Tabs defaultValue='details' className='w-full'>
          <TabsList>
            <TabsTrigger value='details'>Details</TabsTrigger>
            <TabsTrigger value='ingredients'>Ingredients</TabsTrigger>
          </TabsList>
          <TabsContent value='details'>
            <div className='w-[calc(100%+0.75rem)] max-h-[60vh] overflow-y-auto py-1 pe-3'>
              <Form {...form}>
                <form
                  id='recipe-form'
                  onSubmit={form.handleSubmit(onSubmit)}
                  className='space-y-4 px-0.5'
                >
                  <FormField
                    control={form.control}
                    name='title'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Title
                        </FormLabel>
                        <FormControl>
                          <Input
                            placeholder='Adobong Manok'
                            className='col-span-4'
                            autoComplete='off'
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='description'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Description
                        </FormLabel>
                        <FormControl>
                          <Textarea
                            placeholder='Short description of the recipe'
                            className='col-span-4'
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='category'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Category
                        </FormLabel>
                        <FormControl>
                          <Input
                            placeholder='Ulam, Merienda, ...'
                            className='col-span-4'
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='source'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Source
                        </FormLabel>
                        <SelectDropdown
                          defaultValue={field.value}
                          onValueChange={field.onChange}
                          placeholder='Select a source'
                          className='col-span-4'
                          items={sources.map(({ label, value }) => ({
                            label,
                            value,
                          }))}
                        />
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='budget_tag'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Budget Tag
                        </FormLabel>
                        <SelectDropdown
                          defaultValue={field.value}
                          onValueChange={field.onChange}
                          placeholder='Select a budget tag'
                          className='col-span-4'
                          items={budgetTags.map(({ label, value }) => ({
                            label,
                            value,
                          }))}
                        />
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='user_id'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Owner User ID
                        </FormLabel>
                        <FormControl>
                          <Input
                            type='number'
                            placeholder='Leave blank for an official recipe'
                            className='col-span-4'
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='estimated_cost'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Estimated Cost (₱)
                        </FormLabel>
                        <FormControl>
                          <Input
                            type='number'
                            step='0.01'
                            className='col-span-4'
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='servings'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Servings
                        </FormLabel>
                        <FormControl>
                          <Input
                            type='number'
                            className='col-span-4'
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='prep_time_minutes'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Prep Time (min)
                        </FormLabel>
                        <FormControl>
                          <Input
                            type='number'
                            className='col-span-4'
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='cook_time_minutes'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Cook Time (min)
                        </FormLabel>
                        <FormControl>
                          <Input
                            type='number'
                            className='col-span-4'
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='difficulty'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Difficulty
                        </FormLabel>
                        <SelectDropdown
                          defaultValue={field.value}
                          onValueChange={field.onChange}
                          placeholder='Select a difficulty'
                          className='col-span-4'
                          items={difficultyItems}
                        />
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='image_url'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Image URL
                        </FormLabel>
                        <FormControl>
                          <Input
                            placeholder='https://...'
                            className='col-span-4'
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='is_published'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Published
                        </FormLabel>
                        <FormControl>
                          <Switch
                            checked={field.value}
                            onCheckedChange={field.onChange}
                            className='col-span-4 justify-self-start'
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='is_premium_only'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Premium Only
                        </FormLabel>
                        <FormControl>
                          <Switch
                            checked={field.value}
                            onCheckedChange={field.onChange}
                            className='col-span-4 justify-self-start'
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='steps'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Steps
                        </FormLabel>
                        <FormControl>
                          <Textarea
                            placeholder={'One step per line'}
                            className='col-span-4'
                            rows={4}
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='tips'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Tips
                        </FormLabel>
                        <FormControl>
                          <Textarea
                            placeholder={'One tip per line'}
                            className='col-span-4'
                            rows={3}
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='tags'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Tags
                        </FormLabel>
                        <FormControl>
                          <Textarea
                            placeholder={'One tag per line'}
                            className='col-span-4'
                            rows={2}
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={form.control}
                    name='dietary_flags'
                    render={({ field }) => (
                      <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                        <FormLabel className='col-span-2 text-end'>
                          Dietary Flags
                        </FormLabel>
                        <FormControl>
                          <Textarea
                            placeholder={'One flag per line, e.g. vegetarian'}
                            className='col-span-4'
                            rows={2}
                            {...field}
                          />
                        </FormControl>
                        <FormMessage className='col-span-4 col-start-3' />
                      </FormItem>
                    )}
                  />
                </form>
              </Form>
            </div>
          </TabsContent>
          <TabsContent value='ingredients'>
            <div className='max-h-[60vh] overflow-y-auto py-1'>
              {isEdit && currentRow ? (
                <RecipeIngredientsEditor recipeId={currentRow.id} />
              ) : (
                <p className='text-muted-foreground py-6 text-center text-sm'>
                  Save the recipe first to manage its ingredients.
                </p>
              )}
            </div>
          </TabsContent>
        </Tabs>
        <DialogFooter>
          <Button type='submit' form='recipe-form' disabled={isPending}>
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
