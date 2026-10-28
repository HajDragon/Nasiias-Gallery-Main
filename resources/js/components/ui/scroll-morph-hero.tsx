"use client"

// --- Utility ---
// function cn(...inputs: ClassValue[]) {
//     return twMerge(clsx(inputs));
// }

// --- Types ---

// --- FlipCard Component ---
// Reduced from 100
// Reduced from 140
// Smoothly animate to the coordinates defined by the parent

// Initial style
// Essential for the 3D hover effect
/* Front Face */ /* Back Face */

// --- Main Hero Component ---
// Virtual scroll range

// Unsplash Images

// Helper for linear interpolation

// --- Container Size ---

// Initial set

// --- Virtual Scroll Logic ---
// Keep track of scroll value without re-renders
// At either end of the virtual range, release the wheel so the
// page below the hero can scroll normally

// Prevent default to stop browser overscroll/bounce

// Touch support

// Attach listeners to container instead of window for portability

// 1. Morph Progress: 0 (Circle) -> 1 (Bottom Arc)
// Happens between scroll 0 and 600

// 2. Scroll Rotation (Shuffling): Starts after morph (e.g., > 600)
// Rotates the bottom arc as user continues scrolling

// --- Mouse Parallax ---

// Normalize -1 to 1
// Move +/- 100px

// --- Intro Sequence ---

// --- Random Scatter Positions ---

// --- Render Loop (Manual Calculation for Morph) ---

// --- Content Opacity ---
// Fade in content when arc is formed (morphValue > 0.8)
/* Container */ /* Intro Text (Fades out) */ /* Arc Active Content (Fades in) */ /* Main Container */

// 1. Intro Phases (Scatter -> Line)
// Adjusted for smaller images (60px width + 10px gap)
// 2. Circle Phase & Morph Logic

// Responsive Calculations

// A. Calculate Circle Position

// B. Calculate Bottom Arc Position
// "Rainbow" Arch: Convex up. Center is highest point.

// Radius:

// Position:

// Spread angle:

// Apply Scroll Rotation (Shuffle) with Bounds
// We want to clamp rotation so images don't disappear.
// Map scroll range [600, 3000] to a limited rotation range.
// Range: [-spreadAngle/2, spreadAngle/2] keeps them roughly in view.
// We map 0 -> 1 (progress of scroll loop) to this range.

// Note: rotateValue comes from smoothScrollRotate which maps [600, 3000] -> [0, 360]
// We need to adjust that mapping in the hook above, OR adjust it here.
// Better to adjust it here relative to the spread.

// Let's interpret rotateValue (0 to 360) as a progress 0 to 1

// Calculate bounded rotation:
// Move from 0 (centered) to -spreadAngle (all the way left) or similar.
// Let's allow scrolling through the list.
// Total sweep needed to see all items if we start at one end?
// If we start centered, we can go +/- spreadAngle/2.

// User wants to "stop on the last image".
// Let's map scroll to: 0 -> -spreadAngle (shifts items left)
// Don't go all the way, keep last item visible
// Increased scale for active state

// C. Interpolate (Morph)
// Pass intro phase for initial animations
// Virtual scroll value for each of the 3 stages: circle quote, arc quote, exit
// Skip the auto intro if the user clicks before it finishes

// Capture wheel/touch until the final stage, where the hero releases
// the page and the user can scroll past it
// Stage 3: cards fall out of view
// Quote 2: fades in for stage 2, out for stage 3

// Stage 3: fall out of view while fading

// Stage 3 plays the exit, then snaps back to stage 1

// Stage 1: orbit the circle, frozen while the user hovers
// Third click: snap down to the art library and reset the cycle
// Second click: cards exit while the page snaps to the art library
// First click: scroll just far enough to tuck the header away

import { useState, useEffect, useMemo, useRef } from "react"
import type { MouseEvent as ReactMouseEvent } from "react"
import { motion, useTransform, useSpring, useMotionValue } from "framer-motion"
import LiveOrb from "@/components/ui/live-orb"
export type AnimationPhase = "scatter" | "line" | "circle" | "bottom-strip"

type FlipTarget = {
  x: number
  y: number
  rotation: number
  scale: number
  opacity: number
}

interface FlipCardProps {
  src: string
  alt: string
  index: number
  total: number
  phase: AnimationPhase
  target: FlipTarget
  onClick: (event: ReactMouseEvent<HTMLButtonElement>) => void
  onHoverChange: (hovering: boolean) => void
}
const IMG_WIDTH = 60
const IMG_HEIGHT = 85

function FlipCard({
  src,
  alt,
  onClick,
  onHoverChange,
  index,
  total,
  phase,
  target,
}: FlipCardProps) {
  return (
    <motion.button
      type="button"
      onClick={onClick}
      onMouseEnter={() => onHoverChange(true)}
      onMouseLeave={() => onHoverChange(false)}
      animate={{
        x: target.x,
        y: target.y,
        rotate: target.rotation,
        scale: target.scale,
        opacity: target.opacity,
      }}
      transition={{
        type: "spring",
        stiffness: 90,
        damping: 20,
      }}
      style={{
        position: "absolute",
        width: IMG_WIDTH,
        height: IMG_HEIGHT,
        transformStyle: "preserve-3d",
        perspective: "1000px",
      }}
      className="cursor-pointer group"
    >
      <motion.div
        className="relative h-full w-full"
        style={{ transformStyle: "preserve-3d" }}
        transition={{
          duration: 0.6,
          type: "spring",
          stiffness: 260,
          damping: 20,
        }}
        whileHover={{ rotateY: 180 }}
      >
        {}
        <div
          className="absolute inset-0 h-full w-full overflow-hidden rounded-xl shadow-lg bg-gray-200"
          style={{ backfaceVisibility: "hidden" }}
        >
          <img src={src} alt={alt} className="h-full w-full object-cover" />
          <div className="absolute inset-0 bg-black/10 transition-colors group-hover:bg-transparent" />
        </div>

        {}
        <div
          className="absolute inset-0 h-full w-full overflow-hidden rounded-xl shadow-lg bg-gray-900 flex flex-col items-center justify-center p-4 border border-gray-700"
          style={{ backfaceVisibility: "hidden", transform: "rotateY(180deg)" }}
        >
          <div className="text-center">
            <p className="text-[8px] font-bold text-blue-400 uppercase tracking-widest mb-1">
              View
            </p>
            <p className="text-xs font-medium text-white">Details</p>
          </div>
        </div>
      </motion.div>
    </motion.button>
  )
}
const STAGE_SCROLL = [0, 600, 1400]
const lerp = (start: number, end: number, t: number) =>
  start * (1 - t) + end * t

type HeroItem = {
  image: string
  alt: string
}

interface IntroAnimationProps {
  items: HeroItem[]
  onSelect: (index: number) => void
}

export default function IntroAnimation({
  items,
  onSelect,
}: IntroAnimationProps) {
  const [introPhase, setIntroPhase] = useState<AnimationPhase>("scatter")
  const [containerSize, setContainerSize] = useState({ width: 0, height: 0 })
  const containerRef = useRef<HTMLDivElement>(null)
  const TOTAL_IMAGES = items.length
  useEffect(() => {
    if (!containerRef.current) return

    const handleResize = (entries: ResizeObserverEntry[]) => {
      for (const entry of entries) {
        setContainerSize({
          width: entry.contentRect.width,
          height: entry.contentRect.height,
        })
      }
    }

    const observer = new ResizeObserver(handleResize)
    observer.observe(containerRef.current)
    setContainerSize({
      width: containerRef.current.offsetWidth,
      height: containerRef.current.offsetHeight,
    })

    return () => observer.disconnect()
  }, [])
  const virtualScroll = useMotionValue(0)
  const [stage, setStage] = useState(1)
  const [clicks, setClicks] = useState(0)

  const advanceStage = () => {
    setIntroPhase("circle")
    if (clicks >= 1) {
      setClicks(0)
      setStage(3)
      document
        .getElementById("art-library")
        ?.scrollIntoView({ behavior: "smooth" })
      return
    }
    setClicks(1)
    setStage((current) => (current >= 3 ? 1 : current + 1))
    const heroTop = containerRef.current?.getBoundingClientRect().top ?? 0
    if (heroTop > 0) window.scrollBy({ top: heroTop, behavior: "smooth" })
  }

  useEffect(() => {
    virtualScroll.set(STAGE_SCROLL[stage - 1])
  }, [stage, virtualScroll])
  useEffect(() => {
    if (stage !== 3) return
    const timer = setTimeout(() => setStage(1), 700)
    return () => clearTimeout(timer)
  }, [stage])
  const [orbitPaused, setOrbitPaused] = useState(false)
  const [orbitAngle, setOrbitAngle] = useState(0)

  useEffect(() => {
    if (stage !== 1 || introPhase !== "circle" || orbitPaused) return
    let raf = 0
    let last = performance.now()
    const tick = (now: number) => {
      setOrbitAngle((angle) => (angle + ((now - last) / 30000) * 360) % 360)
      last = now
      raf = requestAnimationFrame(tick)
    }
    raf = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(raf)
  }, [stage, introPhase, orbitPaused])
  const morphProgress = useTransform(virtualScroll, [0, 600], [0, 1])
  const smoothMorph = useSpring(morphProgress, { stiffness: 90, damping: 22 })
  const scrollRotate = useTransform(virtualScroll, [600, 3000], [0, 360])
  const smoothScrollRotate = useSpring(scrollRotate, {
    stiffness: 90,
    damping: 22,
  })
  const exitProgress = useTransform(virtualScroll, [900, 1400], [0, 1])
  const smoothExit = useSpring(exitProgress, { stiffness: 90, damping: 22 })
  const mouseX = useMotionValue(0)
  const smoothMouseX = useSpring(mouseX, { stiffness: 30, damping: 20 })

  useEffect(() => {
    const container = containerRef.current
    if (!container) return

    const handleMouseMove = (e: MouseEvent) => {
      const rect = container.getBoundingClientRect()
      const relativeX = e.clientX - rect.left
      const normalizedX = (relativeX / rect.width) * 2 - 1
      mouseX.set(normalizedX * 100)
    }
    container.addEventListener("mousemove", handleMouseMove)
    return () => container.removeEventListener("mousemove", handleMouseMove)
  }, [mouseX])
  useEffect(() => {
    const timer1 = setTimeout(
      () =>
        setIntroPhase((current) => (current === "scatter" ? "line" : current)),
      500,
    )
    const timer2 = setTimeout(
      () =>
        setIntroPhase((current) => (current === "line" ? "circle" : current)),
      2500,
    )
    return () => {
      clearTimeout(timer1)
      clearTimeout(timer2)
    }
  }, [])
  const scatterPositions = useMemo(() => {
    return items.map(() => ({
      x: (Math.random() - 0.5) * 1500,
      y: (Math.random() - 0.5) * 1000,
      rotation: (Math.random() - 0.5) * 180,
      scale: 0.6,
      opacity: 0,
    }))
  }, [items])
  const [morphValue, setMorphValue] = useState(0)
  const [rotateValue, setRotateValue] = useState(0)
  const [parallaxValue, setParallaxValue] = useState(0)
  const [exitValue, setExitValue] = useState(0)

  useEffect(() => {
    const unsubscribeMorph = smoothMorph.on("change", setMorphValue)
    const unsubscribeRotate = smoothScrollRotate.on("change", setRotateValue)
    const unsubscribeParallax = smoothMouseX.on("change", setParallaxValue)
    const unsubscribeExit = smoothExit.on("change", setExitValue)
    return () => {
      unsubscribeMorph()
      unsubscribeRotate()
      unsubscribeParallax()
      unsubscribeExit()
    }
  }, [smoothMorph, smoothScrollRotate, smoothMouseX, smoothExit])
  const contentFade = useTransform(
    virtualScroll,
    [500, 600, 1050, 1350],
    [0, 1, 1, 0],
  )
  const contentOpacity = useSpring(contentFade, { stiffness: 90, damping: 22 })
  const contentY = useTransform(smoothMorph, [0.8, 1], [20, 0])

  return (
    <div
      ref={containerRef}
      className="relative w-full h-full bg-[#F0F7FF] overflow-hidden"
      onClick={advanceStage}
    >
      {}
      <div className="flex h-full w-full flex-col items-center justify-center perspective-1000">
        {}
        <div className="absolute z-0 flex flex-col items-center justify-center text-center pointer-events-none top-1/2 -translate-y-1/2">
          <motion.h1
            initial={{ opacity: 0, y: 20, filter: "blur(10px)" }}
            animate={
              introPhase === "circle" && morphValue < 0.5
                ? { opacity: 1 - morphValue * 2, y: 0, filter: "blur(0px)" }
                : { opacity: 0, filter: "blur(10px)" }
            }
            transition={{ duration: 1 }}
            className="text-2xl font-medium tracking-tight text-gray-800 md:text-4xl"
          >
            Nastiia's Gallery.
          </motion.h1>
          <motion.button
            type="button"
            initial={{ opacity: 0 }}
            animate={
              introPhase === "circle" && morphValue < 0.5
                ? {
                    opacity: 0.5 - morphValue,
                  }
                : { opacity: 0 }
            }
            transition={{ duration: 1, delay: 0.2 }}
            onClick={(event) => {
              event.stopPropagation()
              advanceStage()
            }}
            className="pointer-events-auto mt-4 text-xs font-bold tracking-[0.2em] text-gray-500"
          >
            CLICK TO EXPLORE
          </motion.button>
          <motion.div
            initial={{ opacity: 0, scale: 0.85 }}
            animate={
              introPhase === "circle" && morphValue < 0.5
                ? { opacity: 1 - morphValue * 2, scale: 1 }
                : { opacity: 0, scale: 0.85 }
            }
            transition={{ duration: 1, delay: 0.3 }}
            className="mt-5"
          >
            <LiveOrb
              size={96}
              variant="custom"
              color="#98C1D9"
              eyeColor="#293241"
            />
          </motion.div>
        </div>

        {}
        <motion.div
          style={{ opacity: contentOpacity, y: contentY }}
          className="absolute top-[10%] z-10 flex flex-col items-center justify-center text-center pointer-events-none px-4"
        >
          <h2 className="text-3xl md:text-5xl font-semibold text-gray-900 tracking-tight mb-4">
            The Collection
          </h2>
          <p className="text-sm md:text-base text-gray-600 max-w-lg leading-relaxed">
            "A painting is only half finished until someone stands before it.
            <br className="hidden md:block" />
            The rest of the art happens entirely within you."
          </p>
          <button
            type="button"
            onClick={(event) => {
              event.stopPropagation()
              advanceStage()
            }}
            className="pointer-events-auto mt-6 text-xs font-bold tracking-[0.2em] text-gray-500"
          >
            CLICK TO CONTINUE
          </button>
          <div className="mt-5 flex justify-center">
            <LiveOrb
              size={96}
              variant="custom"
              color="#98C1D9"
              eyeColor="#293241"
            />
          </div>
        </motion.div>

        {}
        <div className="relative flex items-center justify-center w-full h-full">
          {items.map((item, i) => {
            let target = { x: 0, y: 0, rotation: 0, scale: 1, opacity: 1 }
            if (introPhase === "scatter") {
              target = scatterPositions[i]
            } else if (introPhase === "line") {
              const lineSpacing = 70
              const lineTotalWidth = TOTAL_IMAGES * lineSpacing
              const lineX = i * lineSpacing - lineTotalWidth / 2
              target = { x: lineX, y: 0, rotation: 0, scale: 1, opacity: 1 }
            } else {
              const isMobile = containerSize.width < 768
              const minDimension = Math.min(
                containerSize.width,
                containerSize.height,
              )
              const circleRadius = Math.min(minDimension * 0.35, 350)

              const circleAngle = (i / TOTAL_IMAGES) * 360 + orbitAngle
              const circleRad = (circleAngle * Math.PI) / 180
              const circlePos = {
                x: Math.cos(circleRad) * circleRadius,
                y: Math.sin(circleRad) * circleRadius,
                rotation: circleAngle + 90,
              }
              const baseRadius = Math.min(
                containerSize.width,
                containerSize.height * 1.5,
              )
              const arcRadius = baseRadius * (isMobile ? 1.4 : 1.1)
              const arcApexY = containerSize.height * (isMobile ? 0.35 : 0.25)
              const arcCenterY = arcApexY + arcRadius
              const spreadAngle = isMobile ? 100 : 130
              const startAngle = -90 - spreadAngle / 2
              const step = spreadAngle / (TOTAL_IMAGES - 1)
              const scrollProgress = Math.min(Math.max(rotateValue / 360, 0), 1)
              const maxRotation = spreadAngle * 0.8
              const boundedRotation = -scrollProgress * maxRotation

              const currentArcAngle = startAngle + i * step + boundedRotation
              const arcRad = (currentArcAngle * Math.PI) / 180

              const arcPos = {
                x: Math.cos(arcRad) * arcRadius + parallaxValue,
                y: Math.sin(arcRad) * arcRadius + arcCenterY,
                rotation: currentArcAngle + 90,
                scale: isMobile ? 1.4 : 1.8,
              }
              target = {
                x: lerp(circlePos.x, arcPos.x, morphValue),
                y: lerp(circlePos.y, arcPos.y, morphValue),
                rotation: lerp(circlePos.rotation, arcPos.rotation, morphValue),
                scale: lerp(1, arcPos.scale, morphValue),
                opacity: 1,
              }
            }
            target = {
              ...target,
              y: target.y + exitValue * containerSize.height * 1.1,
              opacity: target.opacity * (1 - exitValue),
            }

            return (
              <FlipCard
                key={i}
                src={item.image}
                alt={item.alt}
                index={i}
                total={TOTAL_IMAGES}
                phase={introPhase}
                target={target}
                onClick={(event) => {
                  event.stopPropagation()
                  onSelect(i)
                }}
                onHoverChange={setOrbitPaused}
              />
            )
          })}
        </div>
      </div>
    </div>
  )
}
