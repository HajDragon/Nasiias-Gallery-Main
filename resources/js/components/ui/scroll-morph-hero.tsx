"use client"

import { useEffect, useRef, useState } from "react"
import { motion, useScroll, useTransform } from "framer-motion"
import { LiveOrb } from "./live-orb"
import { cn } from "@/lib/utils"

interface ScrollMorphHeroProps {
  className?: string
}

export function ScrollMorphHero({ className }: ScrollMorphHeroProps) {
  const containerRef = useRef<HTMLDivElement>(null)
  const [isVisible, setIsVisible] = useState(false)
  const [animating, setAnimating] = useState(false)
  const [showOrb, setShowOrb] = useState(false)

  useEffect(() => {
    const timer = setTimeout(() => {
      setIsVisible(true)
      setTimeout(() => setAnimating(true), 100)
      setTimeout(() => setShowOrb(true), 300)
    }, 200)
    return () => clearTimeout(timer)
  }, [])

  const { scrollYProgress } = useScroll({ target: containerRef, offset: ["start start", "end start"] })

  // Phase 1: Scatter (0-0.2)
  const scatterProgress = useTransform(scrollYProgress, [0, 0.2], [0, 1], { clamp: true })
  // Phase 2: Line (0.2-0.4)
  const lineProgress = useTransform(scrollYProgress, [0.2, 0.4], [0, 1], { clamp: true })
  // Phase 3: Circle/Orbit (0.4-0.7)
  const circleProgress = useTransform(scrollYProgress, [0.4, 0.7], [0, 1], { clamp: true })
  // Phase 4: Bottom strip/arc (0.7-1)
  const arcProgress = useTransform(scrollYProgress, [0.7, 1], [0, 1], { clamp: true })

  const orbScale = useTransform(scrollYProgress, [0, 0.2, 0.4, 0.7, 1], [0, 1, 1, 0.8, 0])
  const orbOpacity = useTransform(scrollYProgress, [0, 0.1, 0.7, 0.8], [0, 1, 1, 0])
  const textOpacity = useTransform(scrollYProgress, [0, 0.2, 0.4, 0.7, 1], [1, 1, 0.5, 0, 0])

  return (
    <motion.div
      ref={containerRef}
      className={cn("relative w-full min-h-screen overflow-hidden bg-[#EEF3F8]", className)}
      initial={{ opacity: 0 }}
      animate={{ opacity: isVisible ? 1 : 0 }}
      transition={{ duration: 1.5, ease: [0.25, 0.46, 0.45, 0.94] }}
    >
      <div className="absolute inset-0 pointer-events-none">
        {/* Ambient background blobs */}
        <motion.div
          className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"
          style={{
            width: "clamp(280px, 60vw, 600px)",
            height: "clamp(280px, 60vw, 600px)",
          }}
          animate={{
            scale: [1, 1.1, 1.2, 1.15, 1],
            borderRadius: [
              "50%",
              "40% 60% 50% 50%",
              "30% 70% 40% 60%",
              "45% 55% 55% 45%",
              "50%",
            ],
          }}
          transition={{ duration: 8, repeat: Infinity, ease: "easeInOut" }}
        >
          <div className="absolute inset-0 rounded-full bg-gradient-to-br from-[#7C6AF7]/20 via-[#7DD3C7]/20 to-[#E8B4D4]/20 blur-[100px]" />
        </motion.div>

        <motion.div
          className="absolute top-1/4 left-1/4 w-96 h-96 rounded-full bg-gradient-to-br from-[#7C6AF7]/10 to-transparent blur-[150px]"
          animate={{ x: [0, 100, -50, 0], y: [0, -80, 60, 0] }}
          transition={{ duration: 12, repeat: Infinity, ease: "easeInOut" }}
        />
        <motion.div
          className="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full bg-gradient-to-br from-[#7DD3C7]/10 to-transparent blur-[150px]"
          animate={{ x: [0, -80, 40, 0], y: [0, 60, -40, 0] }}
          transition={{ duration: 10, repeat: Infinity, ease: "easeInOut", delay: 2 }}
        />
      </div>

      <div className="relative z-10 flex h-screen flex-col items-center justify-center px-5">
        {/* LiveOrb - shown during intro phases */}
        <motion.div
          style={{ opacity: orbOpacity, scale: orbScale }}
          className="flex items-center justify-center"
        >
          {showOrb && (
            <LiveOrb
              variant="custom"
              color="#98C1D9"
              eyeColor="#293241"
              size={96}
              interactive={true}
              blink={true}
            />
          )}
        </motion.div>

        <motion.div
          className="flex flex-col items-center gap-4 text-center"
          initial={{ opacity: 0, y: 40 }}
          animate={{ opacity: isVisible ? 1 : 0, y: 0 }}
          style={{ opacity: textOpacity }}
          transition={{ delay: 0.3, duration: 1, ease: [0.25, 0.46, 0.45, 0.94] }}
        >
          <p className="flex items-center gap-3 text-[10px] font-semibold tracking-[0.24em] uppercase text-[#293241]/60">
            <span className="h-px w-8 bg-[#98C1D9]" />
            Welcome to
          </p>
          <h1 className="font-display text-[clamp(3rem,12vw,7rem)] leading-[0.85] tracking-[-0.04em] text-[#293241]">
            nastiia.gallery
            <br />
            <span className="italic text-[#98C1D9]">art library</span>
          </h1>
        </motion.div>

        {/* Flip Card: Scroll → Explore */}
        <motion.div
          style={{
            width: "clamp(280px, 50vw, 500px)",
            height: "clamp(280px, 50vw, 500px)",
            perspective: 1000,
            transformOrigin: "center center",
          }}
          className="relative mt-8"
          initial={{ opacity: 0 }}
          animate={{ opacity: animating ? 1 : 0 }}
          transition={{ delay: 0.5, duration: 1.2, ease: [0.25, 0.46, 0.45, 0.94] }}
        >
          <motion.div
            style={{
              width: "100%",
              height: "100%",
              transformStyle: "preserve-3d",
              transformOrigin: "center center",
            }}
            animate={{
              rotateX: [
                0,      // Scroll visible
                -90,    // Edge
                -90,    // Edge
                -180,   // Explore visible
                -180,   // Explore visible
                -270,   // Edge
                -270,   // Edge
                -360,   // Back to Scroll
              ],
            }}
            transition={{
              duration: 6,
              repeat: Infinity,
              ease: "linear",
              delay: 0.5,
            }}
          >
            <motion.div
              className="absolute inset-0 flex items-center justify-center rounded-[30px] bg-gradient-to-br from-[#7C6AF7] via-[#7DD3C7] to-[#E8B4D4]"
              style={{ backfaceVisibility: "hidden" }}
            >
              <p className="font-display text-[clamp(2.5rem,8vw,5rem)] leading-[0.9] tracking-[-0.03em] text-[#F0F7FF]">
                Scroll
              </p>
            </motion.div>
            <motion.div
              className="absolute inset-0 flex items-center justify-center rounded-[30px] bg-[#293241]"
              style={{ backfaceVisibility: "hidden", transform: "rotateX(180deg)" }}
            >
              <p className="font-display text-[clamp(2.5rem,8vw,5rem)] leading-[0.9] tracking-[-0.03em] text-[#F0F7FF]">
                Explore
              </p>
            </motion.div>
          </motion.div>
        </motion.div>

        <motion.div
          className="absolute bottom-12 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-center"
          initial={{ opacity: 0 }}
          animate={{ opacity: isVisible ? 1 : 0 }}
          transition={{ delay: 1.5, duration: 1 }}
        >
          <motion.div
            className="flex items-center gap-2 rounded-full bg-[#293241]/10 px-4 py-2"
            animate={{ x: [0, 10, -5, 0] }}
            transition={{ duration: 3, repeat: Infinity, ease: "easeInOut" }}
          >
            <svg className="h-4 w-4 text-[#293241]/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true">
              <path d="M12 5v14M19 12l-7 7-7-7" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            <span className="text-[10px] font-semibold tracking-[0.15em] uppercase text-[#293241]/60">
              Scroll to begin
            </span>
          </motion.div>
        </motion.div>
      </div>

      {/* Bottom Arc - transitions to art library */}
      <div
        className="absolute bottom-0 left-0 right-0 h-[600px] pointer-events-none"
        style={{
          maskImage: "linear-gradient(to top, black 30%, transparent)",
          WebkitMaskImage: "linear-gradient(to top, black 30%, transparent)",
        }}
      >
        <motion.div
          className="absolute inset-0 flex items-center justify-center"
          style={{ transformOrigin: "center center" }}
          animate={{
            scale: [1, 1.02, 1],
            y: [0, 20, 0],
          }}
          transition={{ duration: 6, repeat: Infinity, ease: "easeInOut" }}
        >
          <div className="relative flex w-full max-w-[1440px] h-full items-end justify-center px-5">
            <motion.div
              className="absolute bottom-0 left-1/2 -translate-x-1/2 w-[90%] max-w-[1200px] h-[400px] rounded-t-[60px] bg-gradient-to-t from-[#293241] via-[#293241]/80 to-transparent"
              style={{ opacity: arcProgress }}
            />
          </div>
        </motion.div>
      </div>
    </motion.div>
  )
}

export default ScrollMorphHero